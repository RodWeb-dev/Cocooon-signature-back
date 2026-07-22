<head>
    <style>
        body {
            color: #754d24;
        }
        a,
        a:active,
        a:visited,
        p {
            color: #31200e;
        }
        b {
            color: #754d24;
        }
        h1 {
            background-color: #754d24;
            color: white;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Nouveau message depuis cocoon-signature.fr</h1>

    <h2>Expéditeur</h2>

    <p><b>Nom : </b><?= $data["name"] ?></p>
    <p><b>Mail : </b><a href="mailto:<?= $data["email"] ?>">
        <?= $data["email"] ?></a></p>

    <h2>Sujet :</h2>

    <p><?= $data["subject"] ?></p>
    <p>Le <?= date("j m Y") ?> à <?= date("H:i") ?></p>

    <h2>Demande :</h2>

    <pre style="white-space: pre-wrap;">
        <?= nl2br(htmlspecialchars($data["content"])) ?>
    </pre>
</body>
