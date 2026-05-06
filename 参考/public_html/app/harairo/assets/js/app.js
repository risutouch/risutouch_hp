document.addEventListener('DOMContentLoaded', () => {
    const catalogElem = document.getElementById('product-catalog');
    let productCatalog = [];
    if (catalogElem) {
        try {
            productCatalog = JSON.parse(catalogElem.value || '[]');
        } catch (error) {
            productCatalog = [];
        }
    }

    const productById = {};
    productCatalog.forEach((product) => {
        if (product && product.id) {
            productById[product.id] = product;
        }
    });

    const productByName = {};
    productCatalog.forEach((product) => {
        if (product && product.name) {
            productByName[product.name] = product;
        }
    });

    const applyProductData = (row, product) => {
        if (!row || !product) {
            return;
        }
        const unitInput = row.querySelector('input[name$="[unit]"]');
        const priceInput = row.querySelector('input[name$="[unit_price]"]');
        const quantitySelect = row.querySelector('select[name$="[quantity]"]');
        const returnCheckbox = row.querySelector('input[name$="[is_return]"]');

        if (unitInput && product.unit !== undefined) {
            unitInput.value = product.unit;
        }
        if (priceInput && product.unit_price !== undefined) {
            priceInput.value = product.unit_price;
        }
        if (quantitySelect && product.default_quantity !== undefined && product.default_quantity > 0) {
            quantitySelect.value = product.default_quantity;
        }
        if (returnCheckbox && product.is_return !== undefined) {
            returnCheckbox.checked = !!product.is_return;
        }
    };

    const initialiseItemRow = (row) => {
        const nameInput = row.querySelector('input[name$="[name]"]');
        if (!nameInput) {
            return;
        }

        const applyProductIfExists = () => {
            const product = productByName[nameInput.value] || null;
            if (product) {
                applyProductData(row, product);
            }
        };

        // 品目名が変更されたときに、品目マスタからデータを自動入力
        nameInput.addEventListener('input', applyProductIfExists);

        // datalistで選択されたときにも対応
        nameInput.addEventListener('change', applyProductIfExists);

        // blur時にも確認（datalistで選択した後にフォーカスを失った時）
        nameInput.addEventListener('blur', applyProductIfExists);
    };

    const setupItemsContainer = (container) => {
        const tableBody = container.querySelector('tbody');
        const addButton = container.querySelector('[data-add-item]');
        const template = document.getElementById('item-row-template');
        if (!tableBody || !addButton || !template) {
            return;
        }

        let nextIndex = 0;
        tableBody.querySelectorAll('tr').forEach((row) => {
            const field = row.querySelector('input[name^="items["]');
            if (!field) {
                return;
            }
            const match = field.name.match(/^items\[(\d+)\]/);
            if (match) {
                const idx = parseInt(match[1], 10);
                if (!Number.isNaN(idx) && idx >= nextIndex) {
                    nextIndex = idx + 1;
                }
            }
        });

        const addRow = () => {
            const clone = template.content.cloneNode(true);
            const newRow = clone.querySelector('tr');
            if (!newRow) {
                return;
            }
            const currentIndex = nextIndex;
            nextIndex += 1;
            newRow.querySelectorAll('input, select').forEach((input) => {
                if (input.name && input.name.includes('__NAME__')) {
                    input.name = input.name.replace('__NAME__', `items[${currentIndex}]`);
                }
            });
            tableBody.appendChild(newRow);
            initialiseItemRow(newRow);
        };

        addButton.addEventListener('click', (event) => {
            event.preventDefault();
            addRow();
        });

        container.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-row]');
            if (!button) {
                return;
            }
            event.preventDefault();
            const row = button.closest('tr');
            if (row && tableBody.rows.length > 1) {
                row.remove();
            }
        });

        tableBody.querySelectorAll('tr').forEach((row) => initialiseItemRow(row));
    };

    document.querySelectorAll('[data-items-container]').forEach((container) => {
        setupItemsContainer(container);
    });

    document.querySelectorAll('[data-products-container]').forEach((container) => {
        const tableBody = container.querySelector('tbody');
        const addButton = container.querySelector('[data-add-product]');
        const template = document.getElementById('product-row-template');
        if (!tableBody || !addButton || !template) {
            return;
        }

        let nextIndex = 0;
        tableBody.querySelectorAll('input[name^="products["]').forEach((input) => {
            const match = input.name.match(/^products\[(\d+)\]/);
            if (match) {
                const idx = parseInt(match[1], 10);
                if (!Number.isNaN(idx) && idx >= nextIndex) {
                    nextIndex = idx + 1;
                }
            }
        });

        const addRow = () => {
            const clone = template.content.cloneNode(true);
            const newRow = clone.querySelector('tr');
            if (!newRow) {
                return;
            }
            const currentIndex = nextIndex;
            nextIndex += 1;
            newRow.querySelectorAll('input').forEach((input) => {
                if (input.name && input.name.includes('__NAME__')) {
                    input.name = input.name.replace('__NAME__', `products[${currentIndex}]`);
                }
            });
            tableBody.appendChild(newRow);
        };

        addButton.addEventListener('click', (event) => {
            event.preventDefault();
            addRow();
        });

        container.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-product]');
            if (!button) {
                return;
            }
            event.preventDefault();
            const row = button.closest('tr');
            if (!row) {
                return;
            }
            row.remove();
            if (tableBody.rows.length === 0) {
                addRow();
            }
        });
    });
});
