<?php

declare(strict_types=1);

namespace Cycle\Schema\Renderer\ConsoleRenderer;

interface Formatter
{
    const TITLE_LENGTH = 13;

    public function title(string $title): string;
    public function property(string $string): string;
    public function column(string $string): string;
    public function info(string $string): string;
    public function typecast(string $string): string;
    public function entity(string $string): string;
    public function error(string $string): string;
}
