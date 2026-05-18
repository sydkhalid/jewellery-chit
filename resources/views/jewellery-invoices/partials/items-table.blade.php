@php
    $oldItems = old('items');
    $items = collect($oldItems ?: ($invoice->exists ? $invoice->items : []));

    if ($items->isEmpty()) {
        $items = collect([[
            'item_name' => '',
            'purity' => '',
            'gross_weight' => 0,
            'net_weight' => 0,
            'rate' => $invoice->gold_rate ?? 1,
            'making_charge' => 0,
            'wastage' => 0,
            'gst_amount' => 0,
            'total_amount' => 0,
        ]]);
    }
@endphp

<div class="admin-card-header px-0 pt-0">
    <div>
        <h3>Invoice Items</h3>
        <p>Gold amount is calculated as net weight multiplied by item rate, plus charges and GST.</p>
    </div>
    <button type="button" class="btn btn-sm btn-light" data-jewellery-add-row>
        <i class="bi bi-plus-lg me-1"></i>Add Item
    </button>
</div>

<div class="table-responsive">
    <table class="table table-sm align-middle" data-jewellery-items-table>
        <thead>
            <tr>
                <th style="min-width: 180px;">Item</th>
                <th>Purity</th>
                <th>Gross Wt.</th>
                <th>Net Wt.</th>
                <th>Rate</th>
                <th>Making</th>
                <th>Wastage</th>
                <th>GST</th>
                <th>Total</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
                <tr data-jewellery-item-row>
                    <td>
                        <input type="text" class="form-control form-control-sm" name="items[{{ $index }}][item_name]" value="{{ data_get($item, 'item_name') }}" required>
                        <div class="invalid-feedback" data-error-for="items.{{ $index }}.item_name"></div>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" name="items[{{ $index }}][purity]" value="{{ data_get($item, 'purity') }}">
                    </td>
                    <td>
                        <input type="number" step="0.001" min="0" class="form-control form-control-sm" name="items[{{ $index }}][gross_weight]" data-jewellery-item="gross_weight" value="{{ data_get($item, 'gross_weight', 0) }}" required>
                        <div class="invalid-feedback" data-error-for="items.{{ $index }}.gross_weight"></div>
                    </td>
                    <td>
                        <input type="number" step="0.001" min="0" class="form-control form-control-sm" name="items[{{ $index }}][net_weight]" data-jewellery-item="net_weight" value="{{ data_get($item, 'net_weight', 0) }}" required>
                        <div class="invalid-feedback" data-error-for="items.{{ $index }}.net_weight"></div>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="1" class="form-control form-control-sm" name="items[{{ $index }}][rate]" data-jewellery-item="rate" value="{{ data_get($item, 'rate', $invoice->gold_rate ?? 1) }}" required>
                        <div class="invalid-feedback" data-error-for="items.{{ $index }}.rate"></div>
                    </td>
                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="items[{{ $index }}][making_charge]" data-jewellery-item="making_charge" value="{{ data_get($item, 'making_charge', 0) }}"></td>
                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="items[{{ $index }}][wastage]" data-jewellery-item="wastage" value="{{ data_get($item, 'wastage', 0) }}"></td>
                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="items[{{ $index }}][gst_amount]" data-jewellery-item="gst_amount" value="{{ data_get($item, 'gst_amount', 0) }}"></td>
                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" data-jewellery-item-total value="{{ data_get($item, 'total_amount', 0) }}" readonly></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-light" data-jewellery-remove-row title="Remove">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<template data-jewellery-row-template>
    <tr data-jewellery-item-row>
        <td>
            <input type="text" class="form-control form-control-sm" name="items[__INDEX__][item_name]" required>
            <div class="invalid-feedback" data-error-for="items.__INDEX__.item_name"></div>
        </td>
        <td><input type="text" class="form-control form-control-sm" name="items[__INDEX__][purity]"></td>
        <td>
            <input type="number" step="0.001" min="0" class="form-control form-control-sm" name="items[__INDEX__][gross_weight]" data-jewellery-item="gross_weight" value="0" required>
            <div class="invalid-feedback" data-error-for="items.__INDEX__.gross_weight"></div>
        </td>
        <td>
            <input type="number" step="0.001" min="0" class="form-control form-control-sm" name="items[__INDEX__][net_weight]" data-jewellery-item="net_weight" value="0" required>
            <div class="invalid-feedback" data-error-for="items.__INDEX__.net_weight"></div>
        </td>
        <td>
            <input type="number" step="0.01" min="1" class="form-control form-control-sm" name="items[__INDEX__][rate]" data-jewellery-item="rate" value="1" required>
            <div class="invalid-feedback" data-error-for="items.__INDEX__.rate"></div>
        </td>
        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="items[__INDEX__][making_charge]" data-jewellery-item="making_charge" value="0"></td>
        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="items[__INDEX__][wastage]" data-jewellery-item="wastage" value="0"></td>
        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="items[__INDEX__][gst_amount]" data-jewellery-item="gst_amount" value="0"></td>
        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" data-jewellery-item-total value="0" readonly></td>
        <td>
            <button type="button" class="btn btn-sm btn-light" data-jewellery-remove-row title="Remove">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>
