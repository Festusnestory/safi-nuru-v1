<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Controller;
use App\Models\Inquiry;

/**
 * Admin-facing review list for public marketing-site contact/inquiry
 * submissions (html/material/public-inquiries.php). Reads/updates the
 * public_inquiries table via App\Models\Inquiry, the same model the public
 * contact form (App\Models\Inquiry::insert()) writes through.
 */
final class InquiryController extends Controller
{
    private const ALLOWED_STATUSES = ['new', 'contacted', 'qualified', 'closed', 'spam'];

    public function list(): void
    {
        require_once \NURU_MATERIAL . '/includes/functions.php';
        $this->requireRole(['admin', 'manager']);

        $model = new Inquiry($this->pdo);

        $filter = isset($_GET['status']) && in_array($_GET['status'], self::ALLOWED_STATUSES, true)
            ? $_GET['status']
            : 'all';
        $csrf = Auth::csrfToken('public_inquiry_status');
        $pageError = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $inquiryId = filter_var(
                $_POST['inquiry_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            $newStatus = (string) ($_POST['status'] ?? '');
            $returnFilter = isset($_POST['return_status']) && in_array($_POST['return_status'], self::ALLOWED_STATUSES, true)
                ? $_POST['return_status']
                : 'all';

            if (!Auth::validCsrfToken($_POST['csrf_token'] ?? null, 'public_inquiry_status')) {
                $pageError = 'Your session expired. Refresh the page and try again.';
                http_response_code(400);
            } elseif ($inquiryId === false || !in_array($newStatus, self::ALLOWED_STATUSES, true)) {
                $pageError = 'The enquiry or status selection is invalid.';
                http_response_code(422);
            } else {
                try {
                    $rowCount = $model->updateStatus((int) $inquiryId, $newStatus, (int) $_SESSION['user_id']);

                    if ($rowCount === 0 && !$model->exists((int) $inquiryId)) {
                        throw new \RuntimeException('The enquiry no longer exists.');
                    }

                    \logActivity(
                        (int) $_SESSION['user_id'],
                        'PUBLIC_INQUIRY_STATUS_UPDATED',
                        "Updated website enquiry #{$inquiryId} to {$newStatus}",
                        'public_inquiries'
                    );

                    $location = Bootstrap::portalPath('public-inquiries.php') . '?updated=1';
                    if ($returnFilter !== 'all') {
                        $location .= '&status=' . rawurlencode($returnFilter);
                    }
                    header('Location: ' . $location, true, 303);
                    exit;
                } catch (\Throwable $error) {
                    error_log('Website enquiry update failed: ' . $error->getMessage());
                    $pageError = $error instanceof \RuntimeException
                        ? $error->getMessage()
                        : 'The enquiry could not be updated. Please try again.';
                    http_response_code(500);
                }
            }
        }

        $inquiries = [];
        $statusCounts = array_fill_keys(self::ALLOWED_STATUSES, 0);

        try {
            $statusCounts = $model->countsByStatus(self::ALLOWED_STATUSES);
            $inquiries = $model->listForAdmin($filter !== 'all' ? $filter : null);
        } catch (\Throwable $error) {
            error_log('Website enquiries unavailable: ' . $error->getMessage());
            $pageError = 'Website enquiries are temporarily unavailable. Please try again.';
            http_response_code(500);
        }

        $totalInquiries = array_sum($statusCounts);

        $this->render('admin.inquiries.list', [
            'allowedStatuses' => self::ALLOWED_STATUSES,
            'statusLabels' => [
                'new' => 'New',
                'contacted' => 'Contacted',
                'qualified' => 'Qualified',
                'closed' => 'Closed',
                'spam' => 'Spam',
            ],
            'statusClasses' => [
                'new' => 'bg-primary',
                'contacted' => 'bg-info',
                'qualified' => 'bg-success',
                'closed' => 'bg-secondary',
                'spam' => 'bg-danger',
            ],
            'filter' => $filter,
            'csrf' => $csrf,
            'pageError' => $pageError,
            'inquiries' => $inquiries,
            'statusCounts' => $statusCounts,
            'totalInquiries' => $totalInquiries,
        ]);
    }
}
