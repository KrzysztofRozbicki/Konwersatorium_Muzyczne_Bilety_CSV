<?php if (empty($topCampaigns)): ?>
    <p class="empty">Brak danych o kampaniach.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Kampania</th>
                <th class="num">Sprzedane bilety</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topCampaigns as $i => $row): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= h($row["utm_campaign"]) ?></td>
                    <td class="num"><?= (int) $row["tickets_sold"] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
