<?php

declare(strict_types=1);

namespace Shopper\Sidebar\Traits;

use Closure;
use Illuminate\Support\Collection;
use Shopper\Sidebar\Contracts\Builder\Item;

trait ItemableTrait
{
    protected Collection $items;

    public function item(string $name, ?Closure $callback = null): Item
    {
        if ($this->items->has($name)) {
            $item = $this->items->get($name);
            if (method_exists($item, 'setBadges')) {
                $item->setBadges(collect());
            }
            if (method_exists($item, 'setAppends')) {
                $item->setAppends(collect());
            }
        } else {
            $item = $this->container->make(Item::class);
            $item->setName($name);
        }

        $this->call(
            callback: $callback,
            caller: $item
        );

        $this->addItem($item);

        return $item;
    }

    public function addItem(Item $item): Item
    {
        if ($this->items->has($item->getName())) {
            /** @var \Shopper\Sidebar\Domain\DefaultItem $existing */
            $existing = $this->items->get($item->getName());
            
            if ($item->getWeight() !== 0) {
                $existing->weight($item->getWeight());
            }

            if ($item->getIcon() !== null) {
                $existing->setIcon($item->getIcon());
            }

            if ($item->getUrl() !== '#') {
                $existing->setUrl($item->getUrl());
            }

            if ($existing !== $item) {
                // clear badges before adding new ones
                if (method_exists($existing, 'setBadges')) {
                    $existing->setBadges(collect());
                }

                foreach ($item->getBadges() as $badge) {
                    $existing->addBadge($badge);
                }

                // clear appends before adding new ones
                if (method_exists($existing, 'setAppends')) {
                    $existing->setAppends(collect());
                }

                foreach ($item->getAppends() as $append) {
                    $existing->addAppend($append);
                }
            }
            
            foreach ($item->getItems() as $child) {
                $existing->addItem($child);
            }

            if ($item->isAuthorized()) {
                $existing->setAuthorized(true);
            }
            
            $this->items->put($item->getName(), $existing);
        } else {
            $this->items->put($item->getName(), $item);
        }

        return $item;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItems(): Collection
    {
        return $this->items->sortBy(fn (Item $item): int => $item->getWeight());
    }

    public function hasItems(): bool
    {
        return count($this->items) > 0;
    }
}
