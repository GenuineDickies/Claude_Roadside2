<?php
/**
 * Shared ID/number generators for documents and tickets
 */

/**
 * Generate a document ID with date-based prefix: PREFIX-YYMMDD-001-01
 */
function generate_doc_id(PDO $pdo, string $prefix, string $table, string $column, int $version = 1, ?int $serviceRequestId = null): string {
    $dateStr = date('ymd');
    $versionNum = max(1, $version);
    $versionStr = str_pad((string)$versionNum, 2, '0', STR_PAD_LEFT);

    // For revisions (v2+), reuse the sequence number from the previous version
    if ($versionNum > 1 && $serviceRequestId && $table === 'estimates') {
        $stmt = $pdo->prepare("SELECT {$column} FROM {$table} WHERE service_request_id = ? AND version = ? LIMIT 1");
        $stmt->execute([$serviceRequestId, $versionNum - 1]);
        $prevDocId = $stmt->fetchColumn();
        if (is_string($prevDocId)) {
            $parts = explode('-', $prevDocId);
            // PREFIX-YYMMDD-SEQ-VER format
            if (count($parts) >= 4 && ctype_digit($parts[2])) {
                $sequence = $parts[2]; // keep same sequence
                return "{$prefix}-{$dateStr}-{$sequence}-{$versionStr}";
            }
        }
    }

    $base = "{$prefix}-{$dateStr}-";
    $stmt = $pdo->prepare("SELECT {$column} FROM {$table} WHERE {$column} LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$base . '%']);
    $last = $stmt->fetchColumn();

    $seqNum = 1;
    if (is_string($last)) {
        $parts = explode('-', $last);
        if (count($parts) >= 4 && ctype_digit($parts[2])) {
            $seqNum = max(1, intval($parts[2]) + 1);
        } elseif (preg_match('/(\d+)$/', $last, $match)) {
            // Fallback for legacy formats; uses trailing digits regardless of length
            $seqNum = max(1, intval($match[1]) + 1);
        }
    }

    $sequence = str_pad((string)$seqNum, 3, '0', STR_PAD_LEFT);
    return "{$prefix}-{$dateStr}-{$sequence}-{$versionStr}";
}

/**
 * Generate a ticket number: RR-YYMMDD-001-01
 */
function generate_ticket_number(PDO $pdo): string {
    $dateStr = date('ymd');
    $prefix = "RR-{$dateStr}-";
    $stmt = $pdo->prepare("SELECT ticket_number FROM service_tickets WHERE ticket_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();

    $seqNum = 1;
    if (is_string($last)) {
        $parts = explode('-', $last);
        if (count($parts) >= 4 && ctype_digit($parts[2])) {
            $seqNum = max(1, intval($parts[2]) + 1);
        } elseif (preg_match('/(\d+)$/', $last, $match)) {
            $seqNum = max(1, intval($match[1]) + 1);
        }
    }

    $sequence = str_pad((string)$seqNum, 3, '0', STR_PAD_LEFT);
    $version = '01';

    return "RR-{$dateStr}-{$sequence}-{$version}";
}

function bump_ticket_number_version(string $ticketNumber, int $version): string {
    $versionStr = str_pad((string)max(1, $version), 2, '0', STR_PAD_LEFT);

    // Canonical: RR-YYMMDD-001-01
    if (preg_match('/^(RR|SR)-(\d{6})-(\d{3})-(\d{2})$/', $ticketNumber, $m)) {
        return sprintf('%s-%s-%s-%s', $m[1], $m[2], $m[3], $versionStr);
    }

    // Legacy: RR-YYYYMMDD-0001 (normalize to canonical)
    if (preg_match('/^(RR|SR)-(\d{8})-(\d{4})$/', $ticketNumber, $m)) {
        $date = substr($m[2], 2); // YYYYMMDD -> YYMMDD
        $seq = str_pad((string)intval($m[3]), 3, '0', STR_PAD_LEFT);
        return sprintf('%s-%s-%s-%s', $m[1], $date, $seq, $versionStr);
    }

    return $ticketNumber;
}
