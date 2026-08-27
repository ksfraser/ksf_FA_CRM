<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Entity;

/**
 * Territory reference-data entity.
 *
 * Maps to 0_fa_crm_territories.
 *
 * @see BR-006 (Cross-Module DDL Caching)
 *
 * @since 1.0.0
 */
class Territory
{
    private int $id;
    private string $name;
    private ?string $description;
    private ?string $region;
    private bool $inactive;
    private int $sortOrder;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->name = $data['name'];
        $this->description = $data['description'] ?? null;
        $this->region = $data['region'] ?? null;
        $this->inactive = (bool)($data['inactive'] ?? 0);
        $this->sortOrder = (int)($data['sort_order'] ?? 0);
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getRegion(): ?string { return $this->region; }
    public function isInactive(): bool { return $this->inactive; }
    public function isActive(): bool { return !$this->inactive; }
    public function getSortOrder(): int { return $this->sortOrder; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'region' => $this->region,
            'inactive' => $this->inactive ? 1 : 0,
            'sort_order' => $this->sortOrder,
        ];
    }
}
