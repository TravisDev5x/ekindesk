<?php

namespace App\Exports;

use App\Models\TicketAuditLog;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TicketAuditExport
{
    private const HEADINGS = [
        'ID Log',
        'Ticket ID',
        'Fecha y Hora',
        'Actor',
        'Acción',
        'Cambios Detallados',
        'Dirección IP',
        'Navegador (User Agent)',
    ];

    public function __construct(
        private ?string $startDate = null,
        private ?string $endDate = null,
        private ?array $ticketIds = null,
    ) {}

    public function query()
    {
        $query = TicketAuditLog::query()->with('user:id,name,email');

        if ($this->startDate) {
            $query->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay());
        }
        if ($this->endDate) {
            $query->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay());
        }
        if ($this->ticketIds !== null && $this->ticketIds !== []) {
            $query->whereIn('ticket_id', $this->ticketIds);
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * Formatea old_values y new_values en un string legible.
     */
    public static function formatChanges(?array $oldValues, ?array $newValues, string $action): string
    {
        if ($action === 'created') {
            return 'Ticket creado';
        }
        if ($action === 'deleted') {
            return 'Ticket eliminado';
        }
        if ($action === 'restored') {
            return 'Ticket restaurado';
        }

        $old = is_array($oldValues) ? $oldValues : [];
        $new = is_array($newValues) ? $newValues : [];
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $parts = [];

        foreach ($keys as $key) {
            $o = array_key_exists($key, $old) ? $old[$key] : null;
            $n = array_key_exists($key, $new) ? $new[$key] : null;
            if ($o === null && $n === null) {
                continue;
            }
            $oldStr = $o === null ? '' : (is_object($o) ? json_encode($o) : (string) $o);
            $newStr = $n === null ? '' : (is_object($n) ? json_encode($n) : (string) $n);
            if ($oldStr === $newStr) {
                continue;
            }
            $parts[] = "{$key}: " . ($oldStr !== '' ? "'{$oldStr}'" : '—') . " -> " . ($newStr !== '' ? "'{$newStr}'" : '—');
        }

        return implode(' | ', $parts) ?: '—';
    }

    public function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Auditoría Tickets');

        $colCount = count(self::HEADINGS);
        $row = 1;

        foreach (self::HEADINGS as $col => $heading) {
            $sheet->setCellValue($this->colLetter($col + 1) . $row, $heading);
        }
        $sheet->getStyle('A1:' . $this->colLetter($colCount) . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $this->colLetter($colCount) . '1')
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        $row++;

        $logs = $this->query()->get();

        foreach ($logs as $log) {
            $sheet->setCellValue('A' . $row, $log->id);
            $sheet->setCellValue('B' . $row, $log->ticket_id);
            $sheet->setCellValue('C' . $row, $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '');
            $sheet->setCellValue('D' . $row, $log->user?->name ?? 'Sistema');
            $sheet->setCellValue('E' . $row, $this->actionLabel($log->action));
            $sheet->setCellValue('F' . $row, self::formatChanges($log->old_values, $log->new_values, $log->action));
            $sheet->setCellValue('G' . $row, $log->ip_address ?? '');
            $sheet->setCellValue('H' . $row, $log->user_agent ?? '');
            $row++;
        }

        foreach (range(1, $colCount) as $col) {
            $sheet->getColumnDimension($this->colLetter($col))->setAutoSize(true);
        }

        return $spreadsheet;
    }

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'created' => 'Creado',
            'updated' => 'Actualizado',
            'deleted' => 'Eliminado',
            'restored' => 'Restaurado',
            default => $action,
        };
    }

    private function colLetter(int $colIndex): string
    {
        $letter = '';
        while ($colIndex > 0) {
            $colIndex--;
            $letter = chr(65 + ($colIndex % 26)) . $letter;
            $colIndex = (int) floor($colIndex / 26);
        }
        return $letter ?: 'A';
    }

    public function exportToPath(string $path): void
    {
        $spreadsheet = $this->buildSpreadsheet();
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }
}
