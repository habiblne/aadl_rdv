<?php

namespace App\Support;

use App\Models\Rdv;
use Vinkla\Hashids\Facades\Hashids;

class RdvHashids
{
    public function encode(Rdv|int $rdv): string
    {
        $id = $rdv instanceof Rdv ? $rdv->id : $rdv;

        return Hashids::connection('rdv')->encode($id);
    }

    public function decode(string $hashid): ?int
    {
        $decoded = Hashids::connection('rdv')->decode($hashid);

        if (count($decoded) !== 1) {
            return null;
        }

        return (int) $decoded[0];
    }

    public function findOrFail(string $hashid): Rdv
    {
        $id = $this->decode($hashid);

        abort_if($id === null, 404);

        return Rdv::query()
            ->with(['dr', 'souscripteur'])
            ->findOrFail($id);
    }
}
