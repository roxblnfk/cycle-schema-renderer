<?php

declare(strict_types=1);

namespace Cycle\Schema\Renderer\PhpFileRenderer;

final class VarExporter
{
    private const MIX_LINE_LENGTH = 120;

    public ?string $key;

    /** @var mixed */
    private $value;
    private bool $wrapValue = true;
    private bool $wrapKey;

    public function __construct(?string $key, $value = null, bool $wrapKey = false)
    {
        $this->key = $key;
        $this->value = $value;
        $this->wrapKey = $wrapKey;
    }

    public function __toString()
    {
        $result = '';
        if ($this->key !== null) {
            $result = $this->wrapKey ? "'{$this->key}' => " : "{$this->key} => ";
        }
        return $result . $this->renderValue($this->value);
    }

    public function setValue($value, bool $wrapValue = true): self
    {
        $this->value = $value;
        $this->wrapValue = $wrapValue;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    private function renderValue($value): string
    {
        switch (true) {
            case $value === null:
                return 'null';
            case is_bool($value):
                return $value ? 'true' : 'false';
            case is_array($value):
                return $this->renderArray($value);
            case !$this->wrapValue || is_int($value) || $value instanceof self:
                return (string)$value;
            case is_string($value):
                return "'" . addslashes($value) . "'";
            default:
                return "unserialize('" . addslashes(serialize($value)) . "')";
        }
    }

    private function renderArray(array $value): string
    {
        $aiKeys = $this->isAutoIncrementedKeys($value);
        $inline = $aiKeys && $this->isScalarArrayValues($value);
        if ($inline) {
            $result = $this->renderArrayInline($value, !$aiKeys);
            if (strlen($result) <= self::MIX_LINE_LENGTH) {
                return $result;
            }
        }
        return $this->renderArrayBlock($value, !$aiKeys);
    }

    private function renderArrayInline(array $value, bool $withKeys = true): string
    {
        $elements = [];
        foreach ($value as $key => $item) {
            $str = '';
            if (!$item instanceof self && $withKeys) {
                $str .= is_int($key) ? "{$key} => " : "'{$key}' => ";
            }
            $elements[] = $str . $this->renderValue($item);
        }
        return '[' . implode(', ', $elements) . ']';
    }

    private function renderArrayBlock(array $value, bool $withKeys = true): string
    {
        $result = '[';
        foreach ($value as $key => $item) {
            $result .= "\n";
            if (!$item instanceof self && $withKeys) {
                $result .= is_int($key) ? "{$key} => " : "'{$key}' => ";
            }
            $result .= $this->renderValue($item) . ',';
        }
        return str_replace("\n", "\n    ", $result) . "\n]";
    }

    private function isAutoIncrementedKeys(array $array): bool
    {
        return count($array) === 0 || array_keys($array) === range(0, count($array) - 1);
    }

    private function isScalarArrayValues(array $array): bool
    {
        foreach ($array as $value) {
            if (!is_scalar($value)) {
                return false;
            }
        }
        return true;
    }
}
