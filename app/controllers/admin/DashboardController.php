<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

class DashboardController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $db = Database::getConnection();

        $stats = [
            'pending_rentals'    => (int) $db->query("SELECT COUNT(*) FROM rentals WHERE status = 'PENDING'")->fetchColumn(),
            'pending_extensions' => (int) $db->query("SELECT COUNT(*) FROM rental_extensions WHERE status = 'PENDING'")->fetchColumn(),
            'renting_count'      => (int) $db->query("SELECT COUNT(*) FROM rentals WHERE status = 'RENTING'")->fetchColumn(),
            'due_soon_count'     => (int) $db->query("SELECT COUNT(*) FROM rentals WHERE status = 'RENTING' AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)")->fetchColumn(),
            'overdue_count'      => (int) $db->query("SELECT COUNT(*) FROM rentals WHERE status = 'RENTING' AND end_date < CURDATE()")->fetchColumn(),
        ];

        $stmt = $db->query("
            SELECT r.*,
                   (SELECT COUNT(*) FROM rental_extensions re WHERE re.rental_id = r.id AND re.status = 'PENDING') AS has_pending_ext
            FROM rentals r
            WHERE (r.status = 'RENTING' AND r.end_date <= DATE_ADD(CURDATE(), INTERVAL 2 DAY))
               OR r.status = 'PENDING'
            ORDER BY
                CASE
                    WHEN r.status = 'RENTING' AND r.end_date < CURDATE() THEN 1
                    WHEN r.status = 'RENTING' AND r.end_date <= DATE_ADD(CURDATE(), INTERVAL 2 DAY) THEN 2
                    ELSE 3
                END ASC,
                r.end_date ASC
            LIMIT 12
        ");
        $attentionRentals = $stmt->fetchAll();

        Response::view('admin/dashboard/index', [
            'pageTitle'        => 'Dashboard Quản trị — ProGear Studio',
            'stats'            => $stats,
            'attentionRentals' => $attentionRentals,
        ], 'admin');
    }
}

