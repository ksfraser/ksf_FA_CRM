<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Entity;

/**
 * Realm reference-data entity.
 *
 * Maps to 0_fa_crm_realms.
 *
 * @see BR-006 (Cross-Module DDL Caching)
 *
 * @since 1.0.0
 */
class Realm
{
    private int $id;
    private string $name;
    private ?string $description;
    private bool $requiresQuote;
    private bool $requiresProject;
    private string $defaultStage;
    private ?string $stagesJson;
    private bool $inactive;
    private int $sortOrder;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->name = $data['name'];
        $this->description = $data['description'] ?? null;
        $this->requiresQuote = (bool)($data['requires_quote'] ?? 0);
        $this->requiresProject = (bool)($data['requires_project'] ?? 0);
        $this->defaultStage = $data['default_stage'] ?? 'qualification';
        $this->stagesJson = $data['stages_json'] ?? null;
        $this->inactive = (bool)($data['inactive'] ?? 0);
        $this->sortOrder = (int)($data['sort_order'] ?? 0);
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function requiresQuote(): bool { return $this->requiresQuote; }
    public function requiresProject(): bool { return $this->requiresProject; }
    public function getDefaultStage(): string { return $this->defaultStage; }
    public function getStagesJson(): ?string { return $this->stagesJson; }
    public function isInactive(): bool { return $this->inactive; }
    public function isActive(): bool { return !$this->inactive; }
    public function getSortOrder(): int { return $this->sortOrder; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'requires_quote' => $this->requiresQuote ? 1 : 0,
            'requires_project' => $this->requiresProject ? 1 : 0,
            'default_stage' => $this->defaultStage,
            'stages_json' => $this->stagesJson,
            'inactive' => $this->inactive ? 1 : 0,
            'sort_order' => $this->sortOrder,
        ];
    }
}
