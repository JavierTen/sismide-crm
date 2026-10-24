<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Acceso denegado · Ruta D</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            margin: 0;
            overflow: hidden;
            background: #000;
            color: #fff;
            font-family: Montserrat, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        /* Estrellas: puntos fijos repartidos por la pantalla. */
        .stars, .stars::after {
            position: fixed;
            top: 0;
            left: 0;
            width: 2px;
            height: 2px;
            border-radius: 50%;
            background: transparent;
            box-shadow:
                8vw 16vh #fff, 28vw 20vh #fff, 42vw 19vh #fff, 65vw 10vh #fff, 83vw 14vh #fff,
                97vw 12vh #fff, 89vw 28vh #fff, 98vw 29vh #fff, 4vw 53vh #fff, 7vw 57vh #fff,
                18vw 60vh #fff, 22vw 60vh #fff, 20vw 64vh #fff, 46vw 69vh #fff, 9vw 76vh #fff,
                28vw 83vh #fff, 15vw 87vh #fff, 54vw 91vh #fff, 66vw 79vh #fff, 81vw 73vh #fff,
                91vw 76vh #fff, 94vw 52vh #fff, 70vw 46vh #fff, 31vw 32vh #fff, 60vw 56vh #fff;
        }

        .stars::after {
            content: "";
            width: 3px;
            height: 3px;
            box-shadow:
                28.6vw 20.3vh #fff, 65vw 10.4vh #fff, 89vw 28.6vh #fff, 20vw 64.4vh #fff,
                28.4vw 83vh #fff, 45.6vw 69.5vh #fff, 91.4vw 76.5vh #fff, 8vw 16.6vh #fff;
        }

        main {
            position: relative;
            z-index: 1;
            min-height: 100%;
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            align-items: center;
            gap: 32px;
            padding: 48px 6vw;
        }

        .logo-wrap {
            display: flex;
            justify-content: center;
        }

        /* El logo va quieto y grande; lo que flota es el 403. */
        .logo {
            width: min(620px, 100%);
            height: auto;
            filter: drop-shadow(0 0 24px rgba(239, 68, 68, .25));
        }

        .content {
            text-align: center;
        }

        .code {
            margin: 0;
            font-family: Bungee, Montserrat, ui-sans-serif, system-ui, sans-serif;
            font-size: clamp(6rem, 15vw, 12rem);
            font-weight: 400;
            line-height: 1;
            letter-spacing: .02em;
            color: #fff;
            text-shadow: 0 6px 0 #ef4444, 0 0 32px rgba(239, 68, 68, .45);
            animation: float 6s ease-in-out infinite;
        }

        /* Cada uno con su movimiento: el 403 flota y el cohete "despega". */
        .code-wrap {
            position: relative;
            display: inline-block;
        }

        .rocket {
            position: absolute;
            top: -.35em;
            right: -.55em;
            width: clamp(3rem, 7vw, 6rem);
            height: auto;
            color: #fff;
            font-size: clamp(6rem, 15vw, 12rem);
            filter: drop-shadow(0 0 16px rgba(239, 68, 68, .5));
            animation: launch 2.6s ease-in-out infinite;
        }

        /* Avanza en diagonal hacia arriba, con un leve temblor de motor. */
        @keyframes launch {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            40%      { transform: translate(14px, -16px) rotate(-4deg); }
            50%      { transform: translate(12px, -14px) rotate(2deg); }
            60%      { transform: translate(14px, -16px) rotate(-3deg); }
        }

        .message {
            margin: 24px 0 0;
            font-size: clamp(1rem, 1.8vw, 1.6rem);
            font-weight: 700;
            line-height: 1.35;
        }


        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%      { transform: translateY(-14px) rotate(-3deg); }
        }

        @media (prefers-reduced-motion: reduce) {
            .code, .rocket { animation: none; }
        }

        @media (max-width: 768px) {
            body { overflow: auto; }

            main {
                grid-template-columns: 1fr;
                gap: 16px;
                padding: 48px 16px;
                align-content: center;
            }

            .logo { width: min(340px, 90%); }

            .code { text-shadow: 0 4px 0 #ef4444, 0 0 24px rgba(239, 68, 68, .45); }
        }
    </style>
</head>
<body>
    <div class="stars" aria-hidden="true"></div>

    <main>
        <div class="logo-wrap">
            <img class="logo" src="{{ asset('img/logo.png') }}" alt="Ruta D Magdalena">

        </div>

        <div class="content">
            <div class="code-wrap">
                <h1 class="code">403</h1>
                @svg('heroicon-o-rocket-launch', 'rocket', ['aria-hidden' => 'true'])
            </div>
            <p class="message">
                ¡Acceso denegado!<br>
                No tienes permiso para ver este archivo.
            </p>
        </div>
    </main>
</body>
</html>
