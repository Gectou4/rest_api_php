<?php

declare(strict_types=1);

namespace G4\Api\App;

/**
 * Enveloppe de réponse API cohérente.
 *
 * Succès : {"status": "success", "data": ...}
 * Erreur : {"status": "error", "message": "..."}
 */
final class ApiResponse
{
    public static function success(mixed $data = null): array
    {
        return ['status' => 'success', 'data' => $data];
    }

    public static function error(string $message): array
    {
        return ['status' => 'error', 'message' => $message];
    }
}
