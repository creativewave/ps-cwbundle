<?php

require_once _PS_ROOT_DIR_.'/vendor/autoload.php';

class CWBundle extends Module
{
    /**
     * Registered hooks.
     *
     * @var array
     */
    const HOOKS = [
        'actionAdminControllerSetMedia',
        'actionAdminModulesOptionsModifier',
        'actionAdminProductsControllerDuplicateAfter',
        'actionCartSave',
        'actionProductAdd',
        'actionProductDelete',
        'actionProductUpdate',
        'displayAdminProductsExtra',
        'displayBundledProducts',
        'displayHeader',
    ];

    /**
     * Installed models.
     *
     * @var array
     */
    const MODELS = ['Bundle'];

    /**
     * Options fields.
     *
     * @var array
     */
    const OPTIONS = [
        'DISPLAY_IMAGE' => [
            'type'       => 'bool',
            'title'      => 'Display bundled products images', /* ->l('Display bundled products images') */
            'default'    => true,
            'validation' => 'isBool',
            'cast'       => 'intval',
        ],
        'DISPLAY_PRICE' => [
            'type'       => 'bool',
            'title'      => 'Display bundled products prices', /* ->l('Display bundled products prices') */
            'default'    => true,
            'validation' => 'isBool',
            'cast'       => 'intval',
        ],
        'DISPLAY_QUANTITY' => [
            'type'       => 'radio',
            'title'      => 'Display mode', /* ->l('Display mode') */
            'choices'    => [
                'Use checkboxes to add bundled products', /* ->l('Use checkboxes to add bundled products') */
                'Use quantities selectors to add bundled products', /* ->l('Use quantities selectors to add bundled products') */
            ],
            'default'    => 0,
            'validation' => 'isBool',
            'cast'       => 'intval',
        ],
    ];

    /**
     * @see ModuleCore
     */
    public $name    = 'cwbundle';
    public $tab     = 'merchandizing';
    public $version = '1.0.0';
    public $author  = 'Creative Wave';
    public $need_instance = 0;
    public $bootstrap     = true;
    public $ps_versions_compliancy = [
        'min' => '1.6',
        'max' => '1.6.99.99',
    ];

    /**
     * Initialize module.
     */
    public function __construct()
    {
        parent::__construct();

        $this->displayName      = $this->l('Bundle');
        $this->description      = $this->l('Bundle a product with other optional products.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Install module.
     */
    public function install(): bool
    {
        return parent::install()
               and $this->addHooks(static::HOOKS)
               and $this->addModels(static::MODELS);
    }

    /**
     * Uninstall module.
     */
    public function uninstall(): bool
    {
        $this->_clearCache('*');
        $this->getConfiguration()->removeOptionsValues(array_keys(static::OPTIONS));

        return parent::uninstall() and $this->removeModels(static::MODELS);
    }

    /**
     * @see \CW\Module\Configuration::getContent()
     */
    public function getContent(): string
    {
        return $this->getConfiguration()->getContent();
    }

    /**
     * @see \CW\Module\Configuration::hookActionAdminModulesOptionsModifier()
     */
    public function hookActionAdminModulesOptionsModifier(array $params)
    {
        $this->getConfiguration()->hookActionAdminModulesOptionsModifier($params);
    }

    /**
     * Add JS on admin product page.
     */
    public function hookActionAdminControllerSetMedia(array $params)
    {
        if ($this->isPageAdminProduct()) {
            $this->context->controller->addJS($this->_path.'js/admin-product-tab.js');
        }
    }

    /**
     * Display an extra tab on admin product page.
     */
    public function hookDisplayAdminProductsExtra(array $params): string
    {
        $template_name = 'admin-products-extra.tpl';
        $id_cache = $this->getCacheId();

        if (!$this->isCached($template_name, $id_cache)) {
            // Todo: submit PR to fix this shit when adding new product, then clean this up.
            // if ($this->getValue('updateproduct')) {
            if ($id_product = $this->getValue('id_product')) { // Remove
                // $id_product = $this->getValue('id_product');
                $bundle   = $this->getProductBundle($id_product);
                $headline = $bundle->headline;
                $products = $bundle->getProducts();
            } else {
                $headline = '';
                $products = [];
            }
            $this->setTemplateVars([
                'headline'      => $headline,
                'id_lang'       => $this->getContextLanguageId(),
                'is_multistore' => $this->isMultistoreContext(),
                'languages'     => $this->getContextLanguages(),
                'products'      => $products,
                'tab_name'      => $this->getAdminProductTabName(),
            ]);
        }

        return $this->display(__FILE__, $template_name, $id_cache);
    }

    /**
     * Set new product bundle.
     */
    public function hookActionProductAdd(array $params)
    {
        // See CWBundle::hookActionAdminProductsControllerDuplicateAfter()
        $this->new_id_product = $params['id_product'];

        if (!$this->shouldSaveBundle()) {
            return;
        }

        $error = $this->l('An error occurred while attempting to save product bundle.');

        // Save bundle.
        $bundle = $this->getProductBundle($params['id_product']);
        $this->copyBundleFromPost($bundle); // Set bundle props values from $_POST.
        $bundle->id_product = $params['id_product'];
        $bundle->save() or $this->addContextError($error);

        // Save bundled products.
        $ids_bundled_products = $this->getValues('bundled_products_ids');
        $ids_shops = $this->getContextShopsIds();
        $bundle->addProducts($ids_bundled_products, $ids_shops)
        or $this->addContextError($error);
    }

    /**
     * Set updated product bundle.
     */
    public function hookActionProductUpdate(array $params)
    {
        if (!$this->shouldSaveBundle()) {
            return;
        }

        $error = $this->l('An error occurred while attempting to save product bundle.');
        $is_multistore = $this->isMultistoreContext();

        // Save bundle.
        $bundle = $this->getProductBundle($params['id_product']);
        $this->copyBundleFromPost($bundle); // Set bundle props values from $_POST.
        if ($is_multistore) {
            $checked_fields = $this->getValues('multishop_check');
            $bundle->setFieldsToUpdate($checked_fields); // Set bundle props to update.
        }
        $bundle->save() or $this->addContextError($error);

        // Save bundled products.
        if ($is_multistore and !$this->isFieldSubmitted('bundled-products')) {
            return;
        }
        $ids_bundled_products = $this->getValues('bundled_products_ids');
        $ids_shops = $this->getContextShopsIds();
        $bundle->setProducts($ids_bundled_products, $ids_shops)
        or $this->addContextError($error);
    }

    /**
     * Remove deleted product bundle.
     * This hook may never have to do anything. Delete operations on products in
     * database should automatically cascade on each bundle and bundled products
     * rows.
     */
    public function hookActionProductDelete(array $params)
    {
        /*
         * Removed product may not have a bundle ID if:
         *   - delete operations are cascading as expected (see above)
         *   - it was created while `cwbundle` was not enabled
         *   - it has never been updated while `cwbundle` was enabled.
         */
        if (!$id_bundle = $this->getProductBundleId($params['id_product'])) {
            return;
        }

        $bundle = $this->getBundle($id_bundle);
        $ids_shops = $this->getContextShopsIds();
        $error = $this->l('An error occurred while attempting to delete bundled products.');

        ($bundle->removeAllProducts($ids_shops) and $bundle->delete() and $this->_clearCache('*'))
        or $this->addContextError($error);
    }

    /**
     * Set duplicated product bundle.
     *
     * @todo Duplicate bundled products shop by shop.
     */
    public function hookActionAdminProductsControllerDuplicateAfter(array $params)
    {
        $old_id_product = $this->getValue('id_product');
        $old_id_bundle  = $this->getProductBundleId($old_id_product);

        /*
         * Duplicated product may not have a bundle ID if it was created while
         * `cwbundle` was not enabled, and has never been updated while
         * `cwbundle` was enabled.
         */
        if (!$old_id_bundle) {
            return;
        }

        $old_bundle = $this->getBundle($old_id_bundle);
        $new_bundle = $old_bundle->duplicateObject();
        $new_bundle->id_product = $this->new_id_product;
        $ids_bundled_products = $old_bundle->getProductsIds();
        $ids_shops = $this->getContextShopsIds();
        $error = $this->l('An error occurred while attempting to duplicate bundled products.');

        ($new_bundle->save() and $new_bundle->addProducts($ids_bundled_products, $ids_shops))
        or $this->addContextError($error);
    }

    /**
     * Add CSS and JS on public product page.
     */
    public function hookDisplayHeader(array $params): string
    {
        if (!$this->isPagePublicProduct()) {
            return '';
        }
        $this->context->controller->addCSS(__DIR__.'/css/bundled-products.css');
        $this->context->controller->addJS(__DIR__.'/js/bundled-products.js');

        return '';
    }

    /**
     * Display bundled products.
     */
    public function hookDisplayBundledProducts(array $params): string
    {
        if (!$params['product']->available_for_order) {
            return '';
        }

        $template_name = 'bundled-products.tpl';
        $id_cache = $this->getCacheId();

        if (!$this->isCached($template_name, $id_cache)) {
            $id_bundle = $this->getProductBundleId($params['product']->id);
            $options   = $this->getDisplayOptions();
            /*
             * Product may not have a bundle ID if it was created while `cwbundle`
             * was not enabled, and has never been updated while `cwbundle`
             * was enabled.
             */
            if ($id_bundle) {
                $bundle   = $this->getBundle($id_bundle, $this->getContextLanguageId());
                $headline = $bundle->headline;
                $products = $bundle->getProducts($options);
            } else {
                $headline = '';
                $products = [];
            }
            $this->setTemplateVars([
                'headline' => $headline,
                'options'  => $options,
                'products' => $products,
            ]);
        }

        return $this->display(__FILE__, $template_name, $id_cache);
    }

    /**
     * Add selected bundled products to cart.
     */
    public function hookActionCartSave(array $params)
    {
        if (!$this->isActionPublicCartProductAdd()) {
            return;
        }
        // Filter out empty string when no bundled product has been submitted.
        $bundled_products_ids = array_filter($this->getValues('bundled_products_ids'));
        $bundled_products = $this->getBundledProductsValues($bundled_products_ids);
        $this->addBundledProductsToCart($bundled_products);
    }

    /**
     * Add hooks.
     */
    protected function addHooks(array $hooks): bool
    {
        return array_product(array_map([$this, 'registerHook'], $hooks));
    }

    /**
     * Add models.
     */
    protected function addModels(array $models): bool
    {
        return array_product(array_map([$this, 'addModel'], $models));
    }

    /**
     * Add model.
     */
    protected function addModel(string $model): bool
    {
        return (new CW\ObjectModel\Extension(new $model(), $this->getDb()))->install();
    }

    /**
     * Remove models.
     */
    protected function removeModels(array $models): bool
    {
        return array_product(array_map([$this, 'removeModel'], $models));
    }

    /**
     * Remove model.
     */
    protected function removeModel(string $model): bool
    {
        return (new CW\ObjectModel\Extension(new $model(), $this->getDb()))->uninstall();
    }

    /**
     * Get product bundle.
     */
    protected function getProductBundle(int $id_product): Bundle
    {
        return $this->getBundle($this->getProductBundleId($id_product));
    }

    /**
     * Get product bundle ID.
     */
    protected function getProductBundleId(int $id_product): int
    {
        return Bundle::getProductBundleId($id_product);
    }

    /**
     * Copy bundle props values from $_POST.
     */
    protected function copyBundleFromPost(Bundle $bundle)
    {
        foreach ($bundle::$definition['fields'] as $field => $params) {
            if (!empty($params['lang'])) {
                foreach ($this->getContextLanguagesIds() as $id_lang) {
                    $bundle->$field[$id_lang] = $this->getValue("{$field}_{$id_lang}");
                    // Apply default lang value to empty lang value (only for new bundle).
                    if (!$bundle->id and !$bundle->$field[$id_lang]) {
                        $id_lang_default = $this->getContextShopDefaultLanguageId();
                        $bundle->$field[$id_lang] = $this->getValue("{$field}_{$id_lang_default}");
                    }
                }
                continue;
            }
            $bundle->$field = $this->getValue($field, $bundle->$field ?? '');
        }
    }

    /**
     * Get bundle.
     */
    protected function getBundle(int $id_bundle, int $id_lang = null): Bundle
    {
        return new Bundle($id_bundle, $id_lang);
    }

    /**
     * Get submitted bundled products from $_GET/$_POST.
     */
    protected function getBundledProductsValues(array $ids_products): array
    {
        $products = array_map([$this, 'getBundledProductValue'], $ids_products);

        return array_filter($products, function (array $product) {
            return 0 < $product['quantity'];
        });
    }

    /**
     * Get submitted bundled product from $_GET/$_POST.
     */
    protected function getBundledProductValue(int $id_product): array
    {
        $quantity = $this->getConfiguration()->getOptionValue('display_quantity')
            ? $this->getValue("bundled_product_${id_product}_qty")
            : $this->getValue('qty');

        return ['id_product' => $id_product, 'quantity' => $quantity];
    }

    /**
     * Add bundled products to cart.
     */
    protected function addBundledProductsToCart(array $products): bool
    {
        return array_product(array_map([$this, 'addBundledProductToCart'], $products));
    }

    /**
     * Add bundled product to cart.
     */
    protected function addBundledProductToCart(array $product): bool
    {
        /**
         * Prevents an infinite loop by trying to add a product to cart while
         * adding a product to cart...
         */
        static $products_added_to_cart = [];

        if (array_key_exists($product['id_product'], $products_added_to_cart)) {
            return true;
        }
        $products_added_to_cart[$product['id_product']] = $product;

        return $this->context->cart->updateQty($product['quantity'], $product['id_product']);
    }

    /**
     * Add context error.
     */
    protected function addContextError(string $message): string
    {
        return $this->context->controller->errors[] = $message;
    }

    /**
     * Get admin product tab name.
     */
    protected function getAdminProductTabName(): string
    {
        return 'Module'.ucfirst($this->name);
    }

    /**
     * Get \CW\Module\Configuration.
     */
    protected function getConfiguration(): CW\Module\Configuration
    {
        static $instance;

        return $instance ?? $instance = new CW\Module\Configuration($this);
    }

    /**
     * Get context shop default language ID.
     */
    protected function getContextShopDefaultLanguageId(): int
    {
        return Configuration::get('PS_LANG_DEFAULT');
    }

    /**
     * Get context shops IDs.
     */
    protected function getContextShopsIds(): array
    {
        return Shop::getContextListShopID();
    }

    /**
     * Get Db.
     */
    protected function getDb(bool $slave = false): Db
    {
        return Db::getInstance($slave ? _PS_USE_SQL_SLAVE_ : $slave);
    }

    /**
     * Get context language ID.
     */
    protected function getContextLanguageId(): int
    {
        return $this->context->language->id;
    }

    /**
     * Get context lanugages.
     */
    protected function getContextLanguages(): array
    {
        return $this->context->controller->_languages;
    }

    /**
     * Get context languages IDs.
     */
    protected function getContextLanguagesIds(): array
    {
        return Language::getIDs(false);
    }

    /**
     * Get admin controller name.
     */
    protected function getControllerAdminName(): string
    {
        return $this->context->controller->controller_name;
    }

    /**
     * Get public controller name.
     */
    protected function getControllerPublicName(): string
    {
        return Dispatcher::getInstance()->getController();
    }

    /**
     * Get display options.
     */
    protected function getDisplayOptions(): array
    {
        return $this->getConfiguration()->getOptionsValues(array_keys(static::OPTIONS));
    }

    /**
     * Get value from $_GET/$_POST.
     */
    protected function getValue(string $key, string $default = ''): string
    {
        return Tools::getValue($key, $default);
    }

    /**
     * Get values from $_GET/$_POST.
     */
    protected function getValues(string $key, array $default = []): array
    {
        $value = Tools::getValue($key, $default);

        return is_string($value) ? explode(',', $value) : $value;
    }

    /**
     * Wether or not field name has been submitted.
     */
    protected function isFieldSubmitted(string $field): bool
    {
        return $this->context->controller->checkMultishopBox($field);
    }

    /**
     * Wether or not context is multistore.
     */
    protected function isMultistoreContext(): bool
    {
        return Shop::isFeatureActive() and Shop::CONTEXT_SHOP !== Shop::getContext();
    }

    /**
     * Wether or not admin product page is currently loading.
     */
    protected function isPageAdminProduct(): bool
    {
        return 'AdminProducts' === $this->getControllerAdminName()
               and ($this->isSetKey('addproduct') or $this->isSetKey('updateproduct'));
    }

    /**
     * Wether or not public product page is currently loading.
     */
    protected function isPagePublicProduct(): bool
    {
        return 'product' === $this->getControllerPublicName();
    }

    /**
     * Wether or not a public product add to cart action is processing.
     */
    protected function isActionPublicCartProductAdd(): bool
    {
        return isset($this->context->cart) and $this->isSetKey('add');
    }

    /**
     * Wether or not a key is set in $_GET/$_POST.
     */
    protected function isSetKey(string $key): bool
    {
        return Tools::getIsset($key);
    }

    /**
     * Wether or not tab name is submitted.
     */
    protected function isTabSubmitted(string $tab): bool
    {
        $submitted_tabs = $this->getValues('submitted_tabs');

        return in_array($tab, $submitted_tabs, true);
    }

    /**
     * Set template variables.
     */
    protected function setTemplateVars(array $vars): Smarty_Internal_Data
    {
        return $this->smarty->assign($vars);
    }

    /**
     * Wether or not bundle should be saved.
     */
    protected function shouldSaveBundle(): bool
    {
        return !$this->getValue('ajax') and $this->isTabSubmitted($this->getAdminProductTabName());
    }
}
