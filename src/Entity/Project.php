<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Export\Annotation as Exporter;
use App\Validator\Constraints as Constraints;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_projects",
 *     indexes={
 *          @ORM\Index(columns={"customer_id","visible","name"}),
 *          @ORM\Index(columns={"customer_id","visible","id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ProjectRepository")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 *
 * @Constraints\Project
 *
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\VirtualProperty(
 *      "CustomerName",
 *      exp="object.getCustomer() === null ? null : object.getCustomer().getName()",
 *      options={
 *          @Serializer\SerializedName("parentTitle"),
 *          @Serializer\Type(name="string"),
 *          @Serializer\Groups({"Project"})
 *      }
 * )
 * @Serializer\VirtualProperty(
 *      "CustomerAsId",
 *      exp="object.getCustomer() === null ? null : object.getCustomer().getId()",
 *      options={
 *          @Serializer\SerializedName("customer"),
 *          @Serializer\Type(name="integer"),
 *          @Serializer\Groups({"Project", "Team", "Not_Expanded"})
 *      }
 * )
 *
 * @Exporter\Order({"id", "name", "customer", "orderNumber", "orderDate", "start", "end", "budget", "timeBudget", "budgetType", "color", "visible", "teams", "comment", "billable"})
 * @Exporter\Expose("customer", label="customer", exp="object.getCustomer() === null ? null : object.getCustomer().getName()")
 * @ Exporter\Expose("teams", label="team", exp="object.getTeams().toArray()", type="array")
 */
class Project implements EntityWithMetaFields, EntityWithBudget
{
    use BudgetTrait;
    use ColorTrait;

    /**
     * Internal ID
     *
     * @var int|null
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="id", type="integer")
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * Customer for this project
     *
     * @var Customer
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Subresource", "Expanded"})
     * @OA\Property(ref="#/components/schemas/Customer")
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Customer")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $customer;
    /**
     * Project name
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="name")
     *
     * @ORM\Column(name="name", type="string", length=150, nullable=false)
     * @Assert\NotNull()
     * @Assert\Length(min=3, max=150)
     */
    private $name;
    /**
     * Project order number
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project_Entity"})
     *
     * @Exporter\Expose(label="orderNumber")
     *
     * @ORM\Column(name="order_number", type="text", length=50, nullable=true)
     * @Assert\Length(max=50)
     */
    private ?string $orderNumber = null;
    /**
     * Order date for the project
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project_Entity"})
     * @Serializer\Type(name="DateTime<'Y-m-d'>")
     * @Serializer\Accessor(getter="getOrderDate")
     *
     * Attention: Accessor MUST be used, otherwise date will be serialized in UTC.
     *
     * @Exporter\Expose(label="orderDate", type="datetime")
     *
     * @ORM\Column(name="order_date", type="datetime", nullable=true)
     */
    private ?\DateTime $orderDate = null;
    /**
     * Project start date (times before this date cannot be recorded)
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project"})
     * @Serializer\Type(name="DateTime<'Y-m-d'>")
     * @Serializer\Accessor(getter="getStart")
     *
     * Attention: Accessor MUST be used, otherwise date will be serialized in UTC.
     *
     * @Exporter\Expose(label="project_start", type="datetime")
     *
     * @ORM\Column(name="start", type="datetime", nullable=true)
     */
    private ?\DateTime $start = null;
    /**
     * Project end time (times after this date cannot be recorded)
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project"})
     * @Serializer\Type(name="DateTime<'Y-m-d'>")
     * @Serializer\Accessor(getter="getEnd")
     *
     * Attention: Accessor MUST be used, otherwise date will be serialized in UTC.
     *
     * @Exporter\Expose(label="project_end", type="datetime")
     *
     * @ORM\Column(name="end", type="datetime", nullable=true)
     */
    private ?\DateTime $end = null;
    /**
     * @internal used for storing the timezone for "order", "start" and "end" date
     *
     * @ORM\Column(name="timezone", type="string", length=64, nullable=true)
     */
    private ?string $timezone = null;
    /**
     * @internal used for having the localization state of the dates (see $timezone)
     */
    private bool $localized = false;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="comment")
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private ?string $comment = null;
    /**
     * If the project is not visible, times cannot be recorded
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="visible", type="boolean")
     *
     * @ORM\Column(name="visible", type="boolean", nullable=false)
     * @Assert\NotNull()
     */
    private bool $visible = true;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="billable", type="boolean")
     *
     * @ORM\Column(name="billable", type="boolean", nullable=false, options={"default": true})
     * @Assert\NotNull()
     */
    private bool $billable = true;
    /**
     * Meta fields
     *
     * All visible meta (custom) fields registered with this project
     *
     * @var ProjectMeta[]|Collection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project"})
     * @Serializer\Type(name="array<App\Entity\ProjectMeta>")
     * @Serializer\SerializedName("metaFields")
     * @Serializer\Accessor(getter="getVisibleMetaFields")
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ProjectMeta", mappedBy="project", cascade={"persist"})
     */
    private $meta;
    /**
     * Teams
     *
     * If no team is assigned, everyone can access the project (also depends on the teams of the customer)
     *
     * @var Team[]|ArrayCollection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project"})
     * @OA\Property(type="array", @OA\Items(ref="#/components/schemas/Team"))
     *
     * @ORM\ManyToMany(targetEntity="Team", cascade={"persist"}, inversedBy="projects")
     * @ORM\JoinTable(
     *  name="kimai2_projects_teams",
     *  joinColumns={
     *      @ORM\JoinColumn(name="project_id", referencedColumnName="id", onDelete="CASCADE")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="team_id", referencedColumnName="id", onDelete="CASCADE")
     *  }
     * )
     */
    private $teams;
    /**
     * @ORM\Column(name="invoice_text", type="text", nullable=true)
     */
    private ?string $invoiceText = null;
    /**
     * Whether this project allows booking of global activities
     *
     * @ORM\Column(name="global_activities", type="boolean", nullable=false, options={"default": true})
     * @Assert\NotNull()
     */
    private bool $globalActivities = true;

    public function __construct()
    {
        $this->meta = new ArrayCollection();
        $this->teams = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): Project
    {
        $this->customer = $customer;

        return $this;
    }

    public function setName(string $name): Project
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setComment(?string $comment): Project
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setVisible(bool $visible): Project
    {
        $this->visible = $visible;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setBillable(bool $billable): void
    {
        $this->billable = $billable;
    }

    public function isBillable(): bool
    {
        return $this->billable;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?string $orderNumber): Project
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    /**
     * Make sure begin and end date have the correct timezone.
     * This will be called once for each item after being loaded from the database.
     */
    protected function localizeDates()
    {
        if ($this->localized) {
            return;
        }

        if (null === $this->timezone) {
            $this->timezone = date_default_timezone_get();
        }

        $timezone = new \DateTimeZone($this->timezone);

        if (null !== $this->orderDate) {
            $this->orderDate->setTimeZone($timezone);
        }

        if (null !== $this->start) {
            $this->start->setTimeZone($timezone);
        }

        if (null !== $this->end) {
            $this->end->setTimeZone($timezone);
        }

        $this->localized = true;
    }

    public function getOrderDate(): ?\DateTime
    {
        $this->localizeDates();

        return $this->orderDate;
    }

    public function setOrderDate(?\DateTime $orderDate): Project
    {
        $this->orderDate = $orderDate;

        if (null !== $orderDate) {
            $this->timezone = $orderDate->getTimezone()->getName();
        }

        return $this;
    }

    public function getStart(): ?\DateTime
    {
        $this->localizeDates();

        return $this->start;
    }

    public function setStart(?\DateTime $start): Project
    {
        $this->start = $start;

        if (null !== $start) {
            $this->timezone = $start->getTimezone()->getName();
        }

        return $this;
    }

    public function getEnd(): ?\DateTime
    {
        $this->localizeDates();

        return $this->end;
    }

    public function setEnd(?\DateTime $end): Project
    {
        $this->end = $end;

        if (null !== $end) {
            $this->timezone = $end->getTimezone()->getName();
        }

        return $this;
    }

    public function isGlobalActivities(): bool
    {
        return $this->globalActivities;
    }

    public function setGlobalActivities(bool $globalActivities): void
    {
        $this->globalActivities = $globalActivities;
    }

    /**
     * @return Collection|MetaTableTypeInterface[]
     */
    public function getMetaFields(): Collection
    {
        return $this->meta;
    }

    /**
     * @return MetaTableTypeInterface[]
     */
    public function getVisibleMetaFields(): array
    {
        $all = [];
        foreach ($this->meta as $meta) {
            if ($meta->isVisible()) {
                $all[] = $meta;
            }
        }

        return $all;
    }

    public function getMetaField(string $name): ?MetaTableTypeInterface
    {
        foreach ($this->meta as $field) {
            if (strtolower($field->getName()) === strtolower($name)) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @return bool|int|string|null
     */
    public function getMetaFieldValue(string $name)
    {
        $field = $this->getMetaField($name);
        if ($field === null) {
            return null;
        }

        return $field->getValue();
    }

    public function setMetaField(MetaTableTypeInterface $meta): EntityWithMetaFields
    {
        if (null === ($current = $this->getMetaField($meta->getName()))) {
            $meta->setEntity($this);
            $this->meta->add($meta);

            return $this;
        }

        $current->merge($meta);

        return $this;
    }

    public function addTeam(Team $team)
    {
        if ($this->teams->contains($team)) {
            return;
        }

        $this->teams->add($team);
        $team->addProject($this);
    }

    public function removeTeam(Team $team)
    {
        if (!$this->teams->contains($team)) {
            return;
        }
        $this->teams->removeElement($team);
        $team->removeProject($this);
    }

    /**
     * @return Collection<Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function isVisibleAtDate(\DateTime $dateTime): bool
    {
        if (!$this->isVisible()) {
            return false;
        }
        if ($this->getCustomer() !== null && !$this->getCustomer()->isVisible()) {
            return false;
        }
        if ($this->getStart() !== null && $dateTime < $this->getStart()) {
            return false;
        }
        if ($this->getEnd() !== null && $dateTime > $this->getEnd()) {
            return false;
        }

        return true;
    }

    public function getInvoiceText(): ?string
    {
        return $this->invoiceText;
    }

    public function setInvoiceText(?string $invoiceText): void
    {
        $this->invoiceText = $invoiceText;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }

        $currentTeams = $this->teams;
        $this->teams = new ArrayCollection();
        /** @var Team $team */
        foreach ($currentTeams as $team) {
            $this->addTeam($team);
        }

        $currentMeta = $this->meta;
        $this->meta = new ArrayCollection();
        /** @var ProjectMeta $meta */
        foreach ($currentMeta as $meta) {
            $newMeta = clone $meta;
            $newMeta->setEntity($this);
            $this->setMetaField($newMeta);
        }
    }
}
