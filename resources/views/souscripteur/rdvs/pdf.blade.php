<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche RDV {{ $rdv->hashid }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #ffffff;
            color: #111827;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }

        .page {
            padding: 32px 38px;
        }

        .logo {
            display: block;
            height: 98px;
            width: auto;
            margin: 0 auto 12px;
        }

        h1 {
            margin: 0;
            color: #064e3b;
            font-size: 26px;
            font-weight: 700;
            text-align: center;
        }

        .subtitle {
            margin-top: 5px;
            color: #4b5563;
            text-align: center;
        }

        .reference {
            margin: 18px auto 0;
            border: 1px solid #10b981;
            border-radius: 6px;
            padding: 9px 14px;
            width: 62%;
            color: #064e3b;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
        }

        .grid {
            display: table;
            width: 100%;
            margin-top: 30px;
            table-layout: fixed;
        }

        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .column + .column {
            padding-left: 20px;
        }

        .panel {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 14px 16px 8px;
        }

        h2 {
            margin: 0 0 12px;
            color: #064e3b;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 9px 0;
            text-align: left;
            vertical-align: top;
        }

        th {
            width: 36%;
            color: #4b5563;
            font-weight: 700;
        }

        td {
            color: #111827;
            font-weight: 400;
        }

        .status {
            display: inline-block;
            border: 1px solid #047857;
            border-radius: 999px;
            padding: 4px 10px;
            background: #ecfdf5;
            color: #064e3b;
            font-weight: 700;
        }

        .qr {
            margin: 34px auto 0;
            width: 56%;
            text-align: center;
        }

        .qr-box {
            display: inline-block;
            border: 2px solid #064e3b;
            border-radius: 8px;
            padding: 16px;
            background: #ffffff;
        }

        .qr-image {
            display: block;
            height: 190px;
            width: 190px;
        }

        .note {
            margin-top: 10px;
            color: #4b5563;
            font-size: 11px;
        }

        .footer {
            position: fixed;
            right: 38px;
            bottom: 22px;
            left: 38px;
            border-top: 1px solid #d1d5db;
            padding-top: 8px;
            color: #6b7280;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="header">
            @if ($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="AADL" class="logo">
            @endif
            <h1>FICHE DE RENDEZ-VOUS</h1>
            <div class="subtitle">Agence Nationale de l'Amélioration et du Développement du Logement — AADL</div>
            <div class="reference">Référence du rendez-vous : {{ $rdv->hashid }}</div>
            <div class="subtitle">Date de génération : {{ now()->format('Y-m-d H:i') }}</div>
        </header>

        <section class="grid">
            <div class="column">
                <div class="panel">
                    <h2>INFORMATIONS DU SOUSCRIPTEUR</h2>
                    <table>
                        <tr>
                            <th>Code souscripteur</th>
                            <td>{{ $souscripteur->code }}</td>
                        </tr>
                        <tr>
                            <th>Nom</th>
                            <td>{{ $souscripteur->nom }}</td>
                        </tr>
                        <tr>
                            <th>Prénom</th>
                            <td>{{ $souscripteur->prenom }}</td>
                        </tr>
                        <tr>
                            <th>NIN masqué</th>
                            <td>{{ $maskedNin }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="column">
                <div class="panel">
                    <h2>DÉTAILS DU RENDEZ-VOUS</h2>
                    <table>
                        <tr>
                            <th>Structure d'accueil</th>
                            <td>{{ $rdv->dr->nom }}</td>
                        </tr>
                        <tr>
                            <th>Date du rendez-vous</th>
                            <td>{{ $rdv->date->format('Y-m-d') }}</td>
                        </tr>
                        <tr>
                            <th>Motif</th>
                            <td>{{ $rdv->motif }}</td>
                        </tr>
                        <tr>
                            <th>Statut</th>
                            <td><span class="status">{{ $rdv->statut_label }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </section>

        <section class="qr" data-qr-content="{{ $verificationUrl }}">
            <h2>CODE DE VÉRIFICATION</h2>
            <div class="qr-box">
                <img src="{{ $qrCodeImage }}" alt="QR Code de verification" class="qr-image">
            </div>
            <div class="note">Présentez cette fiche lors de votre rendez-vous.</div>
        </section>

        <footer class="footer">
            Document généré automatiquement par la plateforme de gestion des rendez-vous AADL.<br>
            Référence à conserver pour toute vérification liée à ce rendez-vous.
        </footer>
    </main>
</body>
</html>
