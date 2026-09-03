<?php

namespace App\Http\Controllers;

use App\Models\Dr;
use App\Models\Rdv;
use App\Support\RdvHashids;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

class SouscripteurRdvController extends Controller
{
    private const DAILY_LIMIT = 30;
    private const LIST_PER_PAGE = 15;

    public function index(): View
    {
        $rdvs = Auth::guard('souscripteur')
            ->user()
            ->rdvs()
            ->with('dr')
            ->orderByDesc('date')
            ->latest()
            ->paginate(self::LIST_PER_PAGE)
            ->withQueryString();

        return view('souscripteur.rdvs.index', [
            'rdvs' => $rdvs,
        ]);
    }

    public function create(): View
    {
        $souscripteur = Auth::guard('souscripteur')->user()->load('dr');
        $directions = $souscripteur->dr_id
            ? $this->directionsAutorisees($souscripteur)
            : collect();

        return view('souscripteur.rdvs.create', [
            'dr' => $souscripteur->dr,
            'directions' => $directions,
            'minDate' => $this->minimumAppointmentDate()->toDateString(),
            'assignmentError' => $souscripteur->dr_id
                ? null
                : 'Aucune direction regionale n est rattachee a votre compte. Veuillez contacter un administrateur.',
        ]);
    }

    public function indisponibilites(Request $request): JsonResponse
    {
        $souscripteur = Auth::guard('souscripteur')->user();
        $validated = $request->validate([
            'dr_id' => ['required', 'integer'],
        ]);
        $drId = $this->directionAutoriseeId($souscripteur, (int) $validated['dr_id']);

        $datesCompletes = Rdv::query()
            ->where('dr_id', $drId)
            ->whereDate('date', '>=', $this->minimumAppointmentDate()->toDateString())
            ->selectRaw('DATE(date) as date_rdv')
            ->groupBy('date_rdv')
            ->havingRaw('COUNT(*) >= ?', [self::DAILY_LIMIT])
            ->orderBy('date_rdv')
            ->pluck('date_rdv')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->values();

        return response()->json([
            'dates_completes' => $datesCompletes,
        ]);
    }

    public function fiche(string $hashid, RdvHashids $hashids): View|RedirectResponse
    {
        $data = $this->ficheData($hashid, $hashids);

        if ($this->ficheIsUnavailable($data['rdv'])) {
            return redirect()
                ->route('souscripteur.rdvs.index')
                ->withErrors(['rdv' => 'Le rendez-vous doit être accepté avant que la fiche soit disponible.']);
        }

        return view('souscripteur.rdvs.fiche', $data);
    }

    public function pdf(string $hashid, RdvHashids $hashids): Response|RedirectResponse
    {
        $data = $this->ficheData($hashid, $hashids);

        if ($this->ficheIsUnavailable($data['rdv'])) {
            return redirect()
                ->route('souscripteur.rdvs.index')
                ->withErrors(['rdv' => 'Le rendez-vous doit être accepté avant que la fiche soit disponible.']);
        }

        return Pdf::loadView('souscripteur.rdvs.pdf', $data)
            ->setPaper('a4')
            ->download('fiche-rdv-'.$data['rdv']->hashid.'.pdf');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dr_id' => ['required', 'integer'],
            'motif' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
        ]);

        $appointmentDate = Carbon::parse($validated['date'])->startOfDay();

        if ($appointmentDate->lt($this->minimumAppointmentDate())) {
            throw ValidationException::withMessages([
                'date' => 'Le rendez-vous doit etre pris au moins 3 jours a l avance.',
            ]);
        }

        $souscripteur = Auth::guard('souscripteur')->user();
        $drId = $this->directionAutoriseeId($souscripteur, (int) $validated['dr_id']);

        $lockName = $this->capacityLockName($drId, $appointmentDate->toDateString());

        $this->withMysqlLock($lockName, function () use ($souscripteur, $drId, $validated, $appointmentDate) {
            DB::transaction(function () use ($souscripteur, $drId, $validated, $appointmentDate) {
                $hasActiveAppointment = $souscripteur->rdvs()
                    ->whereIn('statut', [
                        Rdv::STATUT_RDV_PRIS,
                        Rdv::STATUT_RDV_ACCEPTE,
                        Rdv::STATUT_RDV_VALIDE,
                    ])
                    ->exists();

                if ($hasActiveAppointment) {
                    throw ValidationException::withMessages([
                        'rdv' => 'Vous avez deja un rendez-vous actif.',
                    ]);
                }

                $dailyCount = Rdv::where('dr_id', $drId)
                    ->whereDate('date', $appointmentDate->toDateString())
                    ->count();

                if ($dailyCount >= self::DAILY_LIMIT) {
                    throw ValidationException::withMessages([
                        'date' => 'La capacite maximale de 30 rendez-vous est atteinte pour cette direction et cette date.',
                    ]);
                }

                Rdv::create([
                    'souscripteur_id' => $souscripteur->id,
                    'dr_id' => $drId,
                    'motif' => $validated['motif'],
                    'date' => $appointmentDate->toDateString(),
                    'statut' => Rdv::STATUT_RDV_PRIS,
                ]);
            });
        });

        return redirect()
            ->route('souscripteur.dashboard')
            ->with('status', 'Rendez-vous cree avec succes.');
    }

    private function minimumAppointmentDate(): Carbon
    {
        return Carbon::today()->addDays(3);
    }

    private function ficheData(string $hashid, RdvHashids $hashids): array
    {
        $rdv = $hashids->findOrFail($hashid)->loadMissing('dr');
        $souscripteur = Auth::guard('souscripteur')->user();

        abort_unless($rdv->souscripteur_id === $souscripteur->id, 404);

        $verificationUrl = route('agent.rdvs.verification', $rdv->hashid);

        return [
            'rdv' => $rdv,
            'souscripteur' => $souscripteur,
            'verificationUrl' => $verificationUrl,
            'qrCode' => QrCode::format('svg')->size(180)->generate($verificationUrl),
            'qrCodeImage' => $this->qrCodeDataUri($verificationUrl),
            'logoDataUri' => $this->logoDataUri(),
            'maskedNin' => $this->maskedNin($souscripteur->nin),
        ];
    }

    private function ficheIsUnavailable(Rdv $rdv): bool
    {
        return $rdv->statut === Rdv::STATUT_RDV_PRIS;
    }

    private function logoDataUri(): string
    {
        $logoPath = public_path('images/aadl-logo.png');

        if (! is_file($logoPath)) {
            return '';
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath));
    }

    private function qrCodeDataUri(string $text): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return '';
        }

        $matrix = Encoder::encode($text, ErrorCorrectionLevel::M())->getMatrix();
        $margin = 4;
        $scale = 6;
        $modules = $matrix->getWidth();
        $imageSize = ($modules + ($margin * 2)) * $scale;
        $image = imagecreatetruecolor($imageSize, $imageSize);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 17, 24, 39);

        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }

                $left = ($x + $margin) * $scale;
                $top = ($y + $margin) * $scale;

                imagefilledrectangle(
                    $image,
                    $left,
                    $top,
                    $left + $scale - 1,
                    $top + $scale - 1,
                    $black
                );
            }
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    private function maskedNin(string $nin): string
    {
        $visibleDigits = 4;

        if (strlen($nin) <= $visibleDigits) {
            return $nin;
        }

        return str_repeat('*', strlen($nin) - $visibleDigits).substr($nin, -$visibleDigits);
    }

    private function souscripteurDrId($souscripteur): int
    {
        if (! $souscripteur->dr_id) {
            throw ValidationException::withMessages([
                'rdv' => 'Aucune direction régionale n’est rattachée à votre compte.',
            ]);
        }

        return (int) $souscripteur->dr_id;
    }

    private function directionGenerale(): ?Dr
    {
        return Dr::where('nom', 'Direction Générale AADL')->first();
    }

    private function directionsAutorisees($souscripteur)
    {
        $ownDrId = $this->souscripteurDrId($souscripteur);
        $generalDrId = $this->directionGenerale()?->id;

        return Dr::query()
            ->whereIn('id', array_values(array_filter([$generalDrId, $ownDrId])))
            ->orderByRaw("CASE WHEN nom = 'Direction Générale AADL' THEN 0 ELSE 1 END")
            ->orderBy('nom')
            ->get();
    }

    private function directionAutoriseeId($souscripteur, int $selectedDrId): int
    {
        $ownDrId = $this->souscripteurDrId($souscripteur);
        $allowedDrIds = array_filter([
            $this->directionGenerale()?->id,
            $ownDrId,
        ]);

        if (! in_array($selectedDrId, $allowedDrIds, true)) {
            throw ValidationException::withMessages([
                'dr_id' => 'La direction sélectionnée n’est pas autorisée pour votre compte.',
            ]);
        }

        return $selectedDrId;
    }

    private function capacityLockName(int $drId, string $date): string
    {
        return "aadl_rdv_capacity_{$drId}_{$date}";
    }

    private function withMysqlLock(string $lockName, callable $callback): mixed
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return $callback();
        }

        $lock = DB::selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lockName]);

        if ((int) ($lock->acquired ?? 0) !== 1) {
            throw ValidationException::withMessages([
                'date' => 'Impossible de verifier la capacite pour cette date. Veuillez reessayer.',
            ]);
        }

        try {
            return $callback();
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
        }
    }
}
