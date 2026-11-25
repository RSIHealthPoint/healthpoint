<?php
/**
 * File: app/Controllers/Controller.php
 * Kelas dasar Control (kelas kontrol pada Sequence Diagram).
 */
declare(strict_types=1);

namespace HealthPoint\Controllers;

use HealthPoint\Core\Database;

abstract class Controller
{
    public function __construct(protected Database $db)
    {
    }
}
