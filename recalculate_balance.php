<?php
/**
 * Recalculate a tenant's balance from the source tables.
 * total_rent_due = sum of all rent_charge rows
 * total_paid     = sum of CONFIRMED payments only
 * outstanding_balance is a generated column, so MySQL updates it automatically.
 * Safe to call repeatedly (idempotent). Creates the balance_record if missing.
 */
function recalculateBalance(PDO $pdo, int $tenant_id): void
{
    $sql = "INSERT INTO balance_record (tenant_id, total_rent_due, total_paid)
            VALUES (
                :t1,
                (SELECT COALESCE(SUM(amount), 0) FROM rent_charge WHERE tenant_id = :t2),
                (SELECT COALESCE(SUM(amount_paid), 0) FROM payment WHERE tenant_id = :t3 AND status = 'Confirmed')
            )
            ON DUPLICATE KEY UPDATE
                total_rent_due = VALUES(total_rent_due),
                total_paid     = VALUES(total_paid)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':t1' => $tenant_id, ':t2' => $tenant_id, ':t3' => $tenant_id]);
}