<?php
declare(strict_types=1);

namespace pvinvestment\classes\Form;

use pvinvestment\classes\Demo\DemoScenarioFactory;

final class ScenarioFormData
{
    /**
     * @param array<string, scalar|null> $values
     */
    private function __construct(private readonly array $values) {}

    public static function defaults(): self
    {
        return new self(DemoScenarioFactory::defaultFormValues());
    }

    /**
     * @param array<string, mixed> $post
     */
    public static function fromPost(array $post): self
    {
        $values = DemoScenarioFactory::defaultFormValues();
        foreach($values as $key => $_) {
            if(array_key_exists($key, $post)) {
                $postedValue = $post[$key];
                $values[$key] = is_scalar($postedValue) || $postedValue === null ? $postedValue : '';
            }
        }

        return new self($values);
    }

    /**
     * @return array<string, scalar|null>
     */
    public function values(): array
    {
        return $this->values;
    }

    public function string(string $key): string
    {
        $value = $this->values[$key] ?? '';

        return trim((string)$value);
    }

    public function float(string $key): float
    {
        return (float)str_replace(',', '.', $this->string($key));
    }

    public function int(string $key): int
    {
        return (int)$this->float($key);
    }

    public function bool(string $key): bool
    {
        return $this->string($key) === '1';
    }

    public function percentRate(string $key): float
    {
        return $this->float($key) / 100.0;
    }
}
