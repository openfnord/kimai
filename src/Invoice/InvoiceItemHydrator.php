<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

interface InvoiceItemHydrator
{
    public function setInvoiceModel(InvoiceModel $model): void;

    public function hydrate(InvoiceItem $item): array;
}
