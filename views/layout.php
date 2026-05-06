<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Sprzedaż biletów</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Sprzedaż biletów</h1>
    </header>

    <main>
        <?php if (!empty($flashMessages)): ?>
            <div class="flash-list">
                <?php foreach ($flashMessages as $flash): ?>
                    <div class="flash flash-<?= h($flash["type"]) ?>">
                        <?= h($flash["message"]) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section>
            <h2>Źródło danych</h2>
            <?php require __DIR__ . "/upload.php"; ?>
        </section>

        <?php if ($pendingInfo !== null): ?>
            <dialog id="preview-dialog" class="preview-dialog">
                <h2>Podgląd załadowanego pliku</h2>
                <?php require __DIR__ . "/preview.php"; ?>
            </dialog>
        <?php endif; ?>

        <section>
            <h2>Filtry</h2>
            <?php require __DIR__ . "/filters.php"; ?>
        </section>

        <section>
            <h2>Lista wydarzeń <span class="muted">(<?= count(
                $events,
            ) ?>)</span></h2>
            <?php require __DIR__ . "/events.php"; ?>
        </section>

        <section>
            <h2>Top 10 kampanii UTM</h2>
            <p class="muted">Sumaryczna liczba sprzedanych biletów (cały zbiór, bez filtrów eventów)</p>
            <?php require __DIR__ . "/utm.php"; ?>
        </section>
    </main>

    <script src="app.js"></script>
</body>
</html>
