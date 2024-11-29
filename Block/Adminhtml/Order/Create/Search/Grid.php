<?php
namespace WB\AdminOrderGrid\Block\Adminhtml\Order\Create\Search;

class Grid extends \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\Config $salesConfig,
        array $data = []
    ) {
        $this->_productFactory = $productFactory;
        $this->_catalogConfig = $catalogConfig;
        $this->_sessionQuote = $sessionQuote;
        $this->_salesConfig = $salesConfig;
        parent::__construct($context, $backendHelper, $productFactory, $catalogConfig, $sessionQuote, $salesConfig, $data);
    }

    /**
     * Prepare collection to be displayed in the grid
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        // Check if the module is enabled in Admin configuration
        $isEnabled = $this->_scopeConfig->isSetFlag(
            'wb_adminordergrid/general/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$isEnabled) {
            return parent::_prepareCollection(); // Use default behavior if module is disabled
        }


        $attributes = $this->_catalogConfig->getProductAttributes();
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $collection = $this->_productFactory->create()->getCollection();
        $collection->setStore(
            $this->getStore()
        )->addAttributeToSelect(
            $attributes
        )->addAttributeToSelect(
            'sku'
        )->addAttributeToSelect(
            'pre_order_status' // Include pre_order_status custom attribute
        )->addStoreFilter()->addAttributeToFilter(
            'type_id',
            $this->_salesConfig->getAvailableProductTypes()
        )->addAttributeToSelect(
            'gift_message_available'
        );

        $collection->joinField(
            'qty_in_stock',
            'cataloginventory_stock_item',
            'qty',
            'product_id=entity_id',
            '{{table}}.stock_id=1 AND {{table}}.website_id=0',
            'left'
        );

        // Join pre_order_status attribute table explicitly
        $collection->getSelect()->joinLeft(
            ['at_pre_order_status' => 'catalog_product_entity_int'],
            'at_pre_order_status.entity_id = e.entity_id AND at_pre_order_status.attribute_id = (
                SELECT attribute_id FROM eav_attribute 
                WHERE attribute_code = "pre_order_status" AND entity_type_id = (
                    SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = "catalog_product"
                )
            )',
            ['pre_order_status' => 'at_pre_order_status.value']
        );

        // Add custom stock status logic
        $collection->getSelect()->columns([
            'stock_status' => new \Zend_Db_Expr(
                "IF(qty <= 0 AND at_pre_order_status.value = 1, 'Pre Order', 
                IF(qty > 0, 'In Stock', 'Out of Stock'))"
            )
        ]);

        $this->setCollection($collection);

        $parent = get_parent_class($this);
        $parentclass = get_parent_class($parent);
        return $parentclass::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        // Check if the module is enabled in Admin configuration
        $isEnabled = $this->_scopeConfig->isSetFlag(
            'wb_adminordergrid/general/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // If the module is disabled, return the parent columns without adding custom columns
        if (!$isEnabled) {
            return parent::_prepareColumns();
        }

        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'index' => 'entity_id'
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Product'),
                'renderer' => \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\Product::class,
                'index' => 'name'
            ]
        );
        $this->addColumn('sku', ['header' => __('SKU'), 'index' => 'sku']);

        $this->addColumn(
            'qty_in_stock',
            [
                'header' => __('Stock Status'),
                'type' => 'text', // Changed to 'text' for custom strings
                'index' => 'stock_status' // Use the custom stock_status column
            ]
        );
        
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'column_css_class' => 'price',
                'type' => 'currency',
                'currency_code' => $this->getStore()->getCurrentCurrencyCode(),
                'rate' => $this->getStore()->getBaseCurrency()->getRate($this->getStore()->getCurrentCurrencyCode()),
                'index' => 'price',
                'renderer' => \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\Price::class
            ]
        );

        $this->addColumn(
            'in_products',
            [
                'header' => __('Select'),
                'type' => 'checkbox',
                'name' => 'in_products',
                'values' => $this->_getSelectedProducts(),
                'index' => 'entity_id',
                'sortable' => false,
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );

        $this->addColumn(
            'qty',
            [
                'filter' => false,
                'sortable' => false,
                'header' => __('Quantity'),
                'renderer' => \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\Qty::class,
                'name' => 'qty',
                'inline_css' => 'qty',
                'type' => 'input',
                'validate_class' => 'validate-number',
                'index' => 'qty'
            ]
        );        

        $parent = get_parent_class($this);
        $parentclass = get_parent_class($parent);

        // Call parent to ensure all default columns are added
        return $parentclass::_prepareColumns();
    }
}
