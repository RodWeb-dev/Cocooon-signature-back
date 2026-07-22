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
    <h1>Réinitialiser le mot de passe Cocoon Signature</h1>

    <h2>Bonjour <?= $data["firstname"] ?></h2>

    <p>Vous avez demandé à réinitialiser votre mot de passe.<br>
        Veuillez utiliser le formulaire dédié en cliquant sur le lient suivant :<br>
        <a href="<?= $data["url"] ?>">Réinitialisation de mon mot de passe.</a>
    </p>

</body>
