<?php

class Bundle extends ObjectModel
{
    /** @var int */
    public $id_product;
    /** @var string */
    public $headline;

    /** @var array */
    public $products = [];

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'          => 'cw_bundle',
        'primary'        => 'id_bundle',
        'multilang'      => true,
        'multilang_shop' => true,
        'multishop'      => true,
        'fields'         => [
            'id_product' => CW\Db\Table\Definition\DefinitionInterface::KEY_FIELD,
            // Multilang
            'headline'   => [
                'type'     => ObjectModel::TYPE_STRING,
                'lang'     => true,
                'validate' => 'isGenericName',
                'size'     => 255,
            ],
        ],
        'associations'   => [
            'product'  => [
                'type'        => ObjectModel::HAS_ONE,
                'object'      => 'Product',
                'association' => 'product',
                'field'       => 'id_product',
            ],
            'products' => [
                'type'        => ObjectModel::HAS_MANY,
                'object'      => 'Product',
                'association' => 'cw_bundle_product',
                'field'       => 'id_product',
                'multishop'   => true, // Used by creativewave/ps-objectmodel-extension.
            ],
        ],
    ];

    /**
     * Add shop tables associations.
     */
    public function __construct(int $id_bundle = null, int $id_lang = null, int $id_shop = null)
    {
        Shop::addTableAssociation(static::$definition['table'], ['type' => 'shop']);
        Shop::addTableAssociation(static::$definition['table'].'_lang', ['type' => 'fk_shop']);
        parent::__construct($id_bundle, $id_lang, $id_shop);
    }

    /**
     * Wether or not this is a new bundle entry.
     */
    public function isNew(): bool
    {
        return !Validate::isLoadedObject($this);
    }

    /**
     * Get bundled products.
     *
     * @todo Fetch an array of data instead of full Product instances.
     */
    public function getProducts(array $options = []): array
    {
        $products = [];
        if ($this->isNew()) { // Avoid an SQL request if product is new.
            return $products;
        }
        foreach ($this->getProductsIds() as $id_product) {
            $product = new Product($id_product, /* $full = */ false, $this->id_lang, $this->id_shop);
            $product->cover = isset($options['display_image']) ? Product::getCover($product->id) : false;
            $products[$id_product] = $product;
        }

        return $products;
    }

    /**
     * Get bundled products IDs.
     */
    public function getProductsIds(): array
    {
        return array_column($this->getDb()->executeS($this->getDbQuery()
            ->select('id_product')
            ->from(static::$definition['associations']['products']['association'])
            ->where("id_bundle = $this->id")
            ->where("id_shop = $this->id_shop")
        ), 'id_product');
    }

    /**
     * Set bundled products.
     */
    public function setProducts(array $ids_products, array $ids_shops): bool
    {
        return $this->removeAllProducts($ids_shops) and $this->addProducts($ids_products, $ids_shops);
    }

    /**
     * Add bundled products.
     */
    public function addProducts(array $ids_products, array $ids_shops): bool
    {
        foreach ($ids_shops as $id_shop) {
            foreach ($ids_products as $id_product) {
                if (!$this->isValidProductId($id_product)) {
                    return false;
                }
                if (!$this->addProduct($id_product, $id_shop)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Add bundled product.
     */
    public function addProduct(int $id_product, int $id_shop): bool
    {
        return $this->getDb()->insert(
            static::$definition['associations']['products']['association'],
            [
                'id_shop'    => $id_shop,
                'id_bundle'  => $this->id,
                'id_product' => $id_product,
            ]
        );
    }

    /**
     * Remove all bundled products.
     */
    public function removeAllProducts(array $ids_shops): bool
    {
        return $this->getDb()->delete(
            static::$definition['associations']['products']['association'],
            "id_bundle = $this->id AND id_shop IN (".implode(',', $ids_shops).')'
        );
    }

    /**
     * Remove bundled products.
     */
    public function removeProducts(array $ids_products, array $ids_shops): bool
    {
        foreach ($ids_shops as $id_shop) {
            foreach ($ids_products as $id_product) {
                if (!$this->removeProduct($id_product, $id_shop)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Remove bundled product.
     */
    public function removeProduct(int $id_product, int $id_shop): bool
    {
        return $this->getDb()->delete(
            static::$definition['associations']['products']['association'],
            "id_bundle      = $this->id
             AND id_product = $id_product
             AND id_shop    = $id_shop"
        );
    }

    /**
     * Get product bundle ID.
     */
    public static function getProductBundleId(int $id_product): int
    {
        return Db::getInstance()->getValue((new DbQuery())
            ->select(static::$definition['primary'])
            ->from(static::$definition['table'])
            ->where("id_product = $id_product")
        );
    }

    /**
     * Get Db.
     */
    protected function getDb(bool $slave = false): Db
    {
        return Db::getInstance($slave ? _PS_USE_SQL_SLAVE_ : $slave);
    }

    /**
     * Get database query builder.
     */
    protected function getDbQuery(): DbQuery
    {
        return new DbQuery();
    }

    /**
     * Wether or not submitted bundled product ID is valid.
     */
    protected function isValidProductId(int $id_product): bool
    {
        return Validate::isUnsignedId($id_product);
    }
}
