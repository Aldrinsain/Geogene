<?php
// GeoGene - shared activity logging helper (used by Staff + Admin modules).
// Records a row into activity_logs. This must NEVER break the page that
// calls it, so any failure (e.g. table not migrated yet) is swallowed.
if (!function_exists('geogene_log_activity')) {
    function geogene_log_activity(PDO $pdo, int $userId, string $activity, ?string $module = null, ?string $description = null, ?string $role = null): void
    {
        try {
            if ($role === null) {
                $role = $_SESSION['staff_role'] ?? null;
            }
            $stmt = $pdo->prepare(
                "INSERT INTO activity_logs (user_id, role, activity, module, description) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $role, $activity, $module, $description]);
        } catch (Throwable $e) {
            // Logging must never break the primary action (e.g. saving a record).
        }
    }
}
