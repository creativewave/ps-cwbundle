<?php

use PHPUnit\Framework\TestCase;

class CWBundleTest extends TestCase
{
    const REQUIRED_HOOKS = [
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
    const REQUIRED_PROPERTIES = [
        'author',
        'confirmUninstall',
        'description',
        'displayName',
        'name',
        'ps_versions_compliancy',
        'tab',
        'version',
    ];
    const REQUIRED_MODELS = ['Bundle'];
    const REQUIRED_OPTIONS = [
        'DISPLAY_IMAGE',
        'DISPLAY_PRICE',
        'DISPLAY_QUANTITY',
    ];

    /**
     * New instance should have required properties.
     */
    public function testInstanceHasRequiredProperties()
    {
        $module = new CWBundle();
        foreach (self::REQUIRED_PROPERTIES as $prop) {
            $this->assertNotNull($module->$prop);
        }
    }

    /**
     * CWBundle::install() should add required hooks and models.
     */
    public function testInstall()
    {
        $mock = $this
            ->getMockBuilder('CWBundle')
            ->setMethods([
                'addHooks',
                'addModels',
            ])
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('addHooks')
            ->with($this->equalTo(self::REQUIRED_HOOKS))
            ->willReturn(true);
        $mock
            ->expects($this->once())
            ->method('addModels')
            ->with($this->equalTo(self::REQUIRED_MODELS))
            ->willReturn(true);

        $mock->install();
    }

    /**
     * CWBundle::uninstall() should clear cache and remove models and
     * configuration options values.
     */
    public function testUninstall()
    {
        $mock_configuration = $this->createMock('CW\Module\Configuration');
        $mock_module = $this
            ->getMockBuilder('CWBundle')
            ->setMethods([
                '_clearCache',
                'getConfiguration',
                'removeModels',
            ])
            ->getMock();

        $mock_module->method('getConfiguration')->willReturn($mock_configuration);

        $mock_module
            ->expects($this->once())
            ->method('_clearCache')
            ->with($this->equalTo('*'));
        $mock_configuration
            ->expects($this->once())
            ->method('removeOptionsValues')
            ->with($this->equalTo(self::REQUIRED_OPTIONS));
        $mock_module
            ->expects($this->once())
            ->method('removeModels')
            ->with($this->equalTo(self::REQUIRED_MODELS))
            ->willReturn(true);

        $mock_module->uninstall();
    }

    /**
     * CWBundle::hookDisplayAdminProductsExtra() should not set template
     * variables if template is already cached.
     */
    public function testDisplayAdminProductsWithCache()
    {
        $mock = $this
            ->getMockBuilder('CWBundle')
            ->setMethods([
                'isCached',
                'setTemplateVars',
            ])
            ->getMock();

        $mock->method('isCached')->willReturn(true);

        $mock->expects($this->never())->method('setTemplateVars');

        $mock->hookDisplayAdminProductsExtra([]);
    }

    /**
     * CWBundle::hookDisplayAdminProductsExtra() should set required template
     * variables.
     */
    public function testDisplayAdminProducts()
    {
        $mock_bundle = $this->createMock('Bundle');
        $mock_module = $this
            ->getMockBuilder('CWBundle')
            ->setMethods([
                'getContextLanguageId',
                'getContextLanguages',
                'getProductBundle',
                'getValue',
                'isCached',
                'isMultistoreContext',
                'setTemplateVars',
            ])
            ->getMock();
        $mock_smarty = $this
            ->getMockBuilder('stdClass')
            ->setMockClassName('Smarty_Internal_Data')
            ->getMock();

        $mock_bundle->method('getProducts')->willReturn($products = [['id_product' => 2]]);
        $mock_bundle->headline = ['Headline in language ID 1', 'Headline in language ID 2'];
        $mock_module->method('getContextLanguageId')->willReturn($id_lang = 1);
        $mock_module->method('getContextLanguages')->willReturn($languages = [1, 2]);
        $mock_module->method('getProductBundle')->willReturn($mock_bundle);
        $mock_module->method('getValue')->willReturn(1);
        $mock_module->method('isCached')->willReturn(false);
        $mock_module->method('isMultistoreContext')->willReturn($is_multistore = true);

        $mock_module
            ->expects($this->once())
            ->method('setTemplateVars')
            ->with($this->equalTo([
                'headline'      => $mock_bundle->headline,
                'id_lang'       => $id_lang,
                'is_multistore' => $is_multistore,
                'languages'     => $languages,
                'products'      => $products,
                'tab_name'      => 'ModuleCwbundle',
            ]))
            ->willReturn($mock_smarty);

        $mock_module->hookDisplayAdminProductsExtra([]);
    }

    /**
     * Provide data to CWBundleTest::testActionProductAddDoNothing().
     */
    public function provideTestActionProductAddDoNothing()
    {
        return [
            'ajax_request'      => ['POST' => ['ajax' => true]],
            'tab_not_submitted' => ['POST' => []],
        ];
    }

    /**
     * CWBundle::hookActionProductAdd() should not add a bundle if request is
     * AJAX or admin product tab isn't submitted.
     *
     * @dataProvider provideTestActionProductAddDoNothing
     */
    public function testActionProductAddDoNothing(array $POST)
    {
        $_POST = $POST;
        $mock = $this
            ->getMockBuilder('CWBundle')
            ->setMethods(['getProductBundle'])
            ->getMock();

        $mock->expects($this->never())->method('getProductBundle');

        $mock->hookActionProductAdd(['id_product' => 1]);
    }

    /**
     * Provide data to CWBundleTest::testActionProductAdd().
     */
    public function provideTestActionProductAdd()
    {
        return [
            'add_product'   => ['POST' => [
                'submitted_tabs'       => ['ModuleCwbundle'],
                'bundled_products_ids' => [2, 3, 4],
                'headline_1'           => 'Headline in language ID 1',
                'headline_2'           => 'Headline in language ID 2',
            ]],
            'default_value' => ['POST' => [
                'submitted_tabs'       => ['ModuleCwbundle'],
                'bundled_products_ids' => [2, 3, 4],
                'headline_1'           => 'Headline in language ID 1',
            ]],
        ];
    }

    /**
     * CWBundle::hookActionProductAdd() should add a bundle and its associated
     * products.
     *
     * @dataProvider provideTestActionProductAdd
     */
    public function testActionProductAdd(array $POST)
    {
        $_POST = $POST;
        $mock_bundle = $this->createMock('Bundle');
        $mock_module = $this
            ->getMockBuilder('CWBundle')
            ->setMethods([
                'getContextLanguagesIds',
                'getContextShopDefaultLanguageId',
                'getContextShopsIds',
                'getProductBundle',
                'shouldSaveBundle',
            ])
            ->getMock();

        $mock_bundle->id = 0;
        $mock_module->method('getContextLanguagesIds')->willReturn([1, 2]);
        $mock_module->method('getContextShopDefaultLanguageId')->willReturn(1);
        $mock_module->method('getContextShopsIds')->willReturn($ids_shops = [1]);
        $mock_module->method('getProductBundle')->willReturn($mock_bundle);
        $mock_module->method('shouldSaveBundle')->willReturn(true);

        $mock_bundle
            ->expects($this->once())
            ->method('save')
            ->willReturn(true);
        $mock_bundle
            ->expects($this->once())
            ->method('addProducts')
            ->with($this->equalTo($POST['bundled_products_ids']), $this->equalTo($ids_shops))
            ->willReturn(true);

        $mock_module->hookActionProductAdd(['id_product' => $id_product = 1]);

        $this->assertSame($id_product, $mock_bundle->id_product);
        $this->assertSame(
            [
                1 => $POST['headline_1'],
                2 => $POST['headline_2'] ?? $POST['headline_1'],
            ],
            $mock_bundle->headline
        );
    }

    /**
     * CWBundle::hookActionProductUpdate() should set bundle fields to update if
     * context is multistore, and should not set its products if the related
     * multistore checkbox isn't checked.
     */
    public function testActionProductUpdateMultistore()
    {
        $mock_bundle = $this->createMock('Bundle');
        $mock_module = $this
            ->getMockBuilder('CWBundle')
            ->setMethods([
                'copyBundleFromPost',
                'getProductBundle',
                'isFieldSubmitted',
                'isMultistoreContext',
                'shouldSaveBundle',
                'getValues',
            ])
            ->getMock();

        $mock_bundle->method('save')->willReturn(true);
        $mock_module->method('getProductBundle')->willReturn($mock_bundle);
        $mock_module->method('getValues')->willReturn($fields_to_update = ['headline']);
        $mock_module->method('isFieldSubmitted')->willReturn(false);
        $mock_module->method('isMultistoreContext')->willReturn(true);
        $mock_module->method('shouldSaveBundle')->willReturn(true);

        $mock_bundle
            ->expects($this->once())
            ->method('setFieldsToUpdate')
            ->with($this->equalTo($fields_to_update));
        $mock_bundle
            ->expects($this->never())
            ->method('setProducts');

        $mock_module->hookActionProductUpdate(['id_product' => $id_product = 1]);
    }

    /**
     * CWBundle::hookActionProductDelete() should delete bundle, its products,
     * and should clear cache.
     */
    public function testActionProductDelete()
    {
        $mock_bundle = $this->createMock('Bundle');
        $mock_module = $this
            ->getMockBuilder('CWBundle')
            ->setMethods([
                '_clearCache',
                'getBundle',
                'getContextShopsIds',
                'getProductBundleId',
            ])
            ->getMock();

        $mock_module->method('getBundle')->willReturn($mock_bundle);
        $mock_module->method('getContextShopsIds')->willReturn($ids_shops = [1, 2]);
        $mock_module->method('getProductBundleId')->willReturn(1);

        $mock_bundle
            ->expects($this->once())
            ->method('removeAllProducts')
            ->willReturn(3);
        $mock_bundle
            ->expects($this->once())
            ->method('delete')
            ->willReturn(true);
        $mock_module
            ->expects($this->once())
            ->method('_clearCache')
            ->willReturn(true);

        $mock_module->hookActionProductDelete(['id_product' => 1]);
    }

    /**
     * CWBundle::hookActionAdminProductsControllerDuplicateAfter() should add
     * duplicated bundle and its products.
     */
    public function testActionProductDuplicate()
    {
        $mock_module = $this
            ->getMockBuilder('CWBundle')
            ->setMethods([
                'getBundle',
                'getContextShopsIds',
                'getProductBundleId',
                'getValue',
            ])
            ->getMock();
        $mock_old_bundle = $this->createMock('Bundle');
        $mock_new_bundle = $this->createMock('Bundle');

        $mock_module->new_id_product = 1;
        $mock_module->method('getBundle')->willReturn($mock_old_bundle);
        $mock_module->method('getContextShopsIds')->willReturn($ids_shops = [1, 2]);
        $mock_module->method('getProductBundleId')->willReturn(1);
        $mock_module->method('getValue')->willReturn(1);
        $mock_old_bundle->method('duplicateObject')->willReturn($mock_new_bundle);
        $mock_old_bundle->method('getProductsIds')->willReturn($ids_bundled_products = [1, 2, 3]);

        $mock_new_bundle
            ->expects($this->once())
            ->method('save')
            ->willReturn(true);
        $mock_new_bundle
            ->expects($this->once())
            ->method('addProducts')
            ->with($this->equalTo($ids_bundled_products), $this->equalTo($ids_shops))
            ->willReturn(true);

        $mock_module->hookActionAdminProductsControllerDuplicateAfter(['id_product' => 1]);

        $this->assertSame($mock_new_bundle->id_product, $mock_module->new_id_product);
    }

    /**
     * CWBundle::hookDisplayBundledProducts() should not set template variables
     * if product isn't available for order.
     */
    public function testDisplayBundledProductsProductNotAvailableForOrder()
    {
        $mock_module = $this
            ->getMockBuilder('CWBundle')
            ->setMethods(['setTemplateVars'])
            ->getMock();
        $mock_product = new stdClass();

        $mock_product->available_for_order = false;

        $mock_module->expects($this->never())->method('setTemplateVars');

        $mock_module->hookDisplayBundledProducts(['product' => $mock_product]);
    }

    /**
     * CWBundle::hookDisplayBundledProducts() should not set template variables
     * if template is already cached.
     */
    public function testDisplayBundledProductsWithCache()
    {
        $mock_module = $this
            ->getMockBuilder('CWBundle')
            ->setMethods([
                'isCached',
                'setTemplateVars',
            ])
            ->getMock();
        $mock_product = new stdClass();

        $mock_product->available_for_order = true;
        $mock_module->method('isCached')->willReturn(true);

        $mock_module->expects($this->never())->method('setTemplateVars');

        $mock_module->hookDisplayBundledProducts(['product' => $mock_product]);
    }

    /**
     * CWBundle::hookDisplayBundledProducts() should set template variables.
     */
    public function testDisplayBundledProducts()
    {
        $mock_bundle = $this->createMock('Bundle');
        $mock_module = $this
            ->getMockBuilder('CWBundle')
            ->setMethods([
                'getBundle',
                'getContextLanguageId',
                'getDisplayOptions',
                'getProductBundleId',
                'isCached',
                'setTemplateVars',
            ])
            ->getMock();
        $mock_product = new stdClass();
        $mock_smarty = $this
            ->getMockBuilder('stdClass')
            ->setMockClassName('Smarty_Internal_Data')
            ->getMock();

        $mock_bundle->method('getProducts')->willReturn($products = ['products']);
        $mock_bundle->headline = 'Headline';
        $mock_module->method('getBundle')->willReturn($mock_bundle);
        $mock_module->method('getContextLanguageId')->willReturn(1);
        $mock_module->method('getDisplayOptions')->willReturn($options = ['options']);
        $mock_module->method('getProductBundleId')->willReturn(1);
        $mock_module->method('isCached')->willReturn(false);
        $mock_product->available_for_order = true;
        $mock_product->id = 1;

        $mock_module
            ->expects($this->once())
            ->method('setTemplateVars')
            ->with($this->equalTo([
                'headline' => $mock_bundle->headline,
                'options'  => $options,
                'products' => $products,
            ]))
            ->willReturn($mock_smarty);

        $mock_module->hookDisplayBundledProducts(['product' => $mock_product]);
    }

    /**
     * CWBundle::hookActionCartSave() should not add bundled products to cart if
     * cart action is initiated by admin.
     */
    public function testHookActionCartSaveNotUser()
    {
        $mock = $this
            ->getMockBuilder('CWBundle')
            ->setMethods([
                'addBundledProductsToCart',
                'isSetKey',
            ])
            ->getMock();

        $mock->method('isSetKey')->willReturn(false);
        $mock->context = new stdClass();
        $mock->context->cart = new stdClass();

        $mock->expects($this->never())->method('addBundledProductsToCart');

        $mock->hookActionCartSave([]);
    }

    /**
     * CWBundle::hookActionCartSave() should not add bundled products to cart if
     * it's not an add product action.
     */
    public function testHookActionCartSaveNotAddProduct()
    {
        $mock_module = $this
            ->getMockBuilder('CWBundle')
            ->setMethods([
                'addBundledProductsToCart',
                'isSetKey',
            ])
            ->getMock();

        $mock_module->method('isSetKey')->willReturn(false);

        $mock_module->expects($this->never())->method('addBundledProductsToCart');

        $mock_module->hookActionCartSave([]);
    }

    /**
     * Provide data to CWBundleTest::testActionProductAdd().
     */
    public function provideTestActionCartSave()
    {
        return [
            'display_quantity_on'  => [
                'display_quantity' => true,
                'submitted'        => [
                    'bundled_products_ids'  => [1, 2],
                    'bundled_product_1_qty' => 5,
                    'bundled_product_2_qty' => -1,
                ],
                'expected'         => [
                    ['id_product' => 1, 'quantity' => 5],
                ],
            ],
            'display_quantity_off' => [
                'display_quantity' => false,
                'submitted'        => [
                    'qty'                  => 3,
                    'bundled_products_ids' => [1, 2],
                ],
                'expected'         => [
                    ['id_product' => 1, 'quantity' => 3],
                    ['id_product' => 2, 'quantity' => 3],
                ],
            ],
        ];
    }

    /**
     * CWBundle::hookActionCartSave() should add expected bundled products to
     * cart with their expected quantities.
     *
     * @dataProvider provideTestActionCartSave
     */
    public function testActionCartSave(bool $display_quantity, array $POST, array $expected)
    {
        $_POST = $POST;
        $mock_configuration = $this->createMock('CW\Module\Configuration');
        $mock_module = $this
            ->getMockBuilder('CWBundle')
            ->setMethods([
                'addBundledProductsToCart',
                'getConfiguration',
                'isActionPublicCartProductAdd',
            ])
            ->getMock();

        $mock_configuration->method('getOptionValue')->willReturn($display_quantity);
        $mock_module->method('getConfiguration')->willReturn($mock_configuration);
        $mock_module->method('isActionPublicCartProductAdd')->willReturn(true);

        $mock_module
            ->expects($this->once())
            ->method('addBundledProductsToCart')
            ->with($this->equalTo($expected));

        $mock_module->hookActionCartSave([]);
    }
}
