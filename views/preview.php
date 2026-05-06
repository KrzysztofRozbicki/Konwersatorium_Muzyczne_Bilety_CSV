<?php /** @var array $pendingInfo, $previewRows */ ?>
<p>
    <strong><?= h($pendingInfo['original']) ?></strong>
    <span class="muted">— znaleziono <?= (int) $pendingInfo['rows'] ?> poprawnych rekordów</span>
</p>

<?php if (!empty($previewRows)): ?>
    <div class="preview-wrap">
        <table>
            <thead>
                <tr>
                    <?php foreach (array_keys($previewRows[0]) as $col): ?>
                        <th><?= h($col) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($previewRows as $row): ?>
                    <tr>
                        <?php foreach ($row as $value): ?>
                            <td>
                                <?php
                                if (is_bool($value)) {
                                    echo $value ? 'true' : 'false';
                                } elseif ($value === null) {
                                    echo '<span class="muted">—</span>';
                                } else {
                                    echo h((string) $value);
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="muted">Wyświetlono pierwsze 5 z <?= (int) $pendingInfo['rows'] ?> rekordów.</p>
<?php endif; ?>

<div class="pending-actions">
    <form method="post" action="index.php" class="inline-form">
        <input type="hidden" name="action" value="activate">
        <button type="submit">Aktywuj ten plik</button>
    </form>
    <form method="post" action="index.php" class="inline-form">
        <input type="hidden" name="action" value="discard">
        <button type="submit" class="btn-secondary">Odrzuć</button>
    </form>
</div>
