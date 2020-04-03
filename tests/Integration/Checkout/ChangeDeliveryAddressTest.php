<?php
/**
 * #PHPHEADER_OXID_LICENSE_INFORMATION#
 */
namespace OxidEsales\EshopCommunity\Tests\Integration\Checkout;

use oxField;
use OxidEsales\EshopCommunity\Core\ShopIdCalculator;
use oxRegistry;
use oxuser;

class ChangeDeliveryAddressTest extends \OxidTestCase
{
    private const TEST_ARTICLE_ID = '1951';
    private const GERMANY_COUNTRY_ID = 'a7c40f631fc920687.20179984';
    private const AUSTRIA_COUNTRY_ID = 'a7c40f6320aeb2ec2.72885259';
    private const SWITZERLAND_COUNTRY_ID = 'a7c40f6321c6f6109.43859248';
    private const BELGIUM_COUNTRY_ID = 'a7c40f632e04633c9.47194042';

    /**
     * Fixture setUp.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Fixture tearDown.
     */
    protected function tearDown(): void
    {
        $this->cleanUpTable('oxuser');
        $this->cleanUpTable('oxuserbaskets');
        $this->cleanUpTable('oxuserbasketitems');

        parent::tearDown();
    }

    public function testVatForBelgiumCountry(): void
    {
        //create active user
        $user = $this->createActiveBelgiumUser(); //Switzerland user

        // Vat will be zero without valid VatID
        $vatSelector = oxRegistry::get('oxVatSelector');
        $this->assertSame(0, (int)$vatSelector->getUserVat($user));
        $this->assertSame(0, (int)$vatSelector->getUserVat($user, true)); //no cache
    }

    /**
     * Verify that the oxVatSelector respects user country changes.
     */
    public function testVatSelectorOnActiveUserCountryChange(): void
    {
        //create active user
        $user = $this->createActiveUser(); //Switzerland user

        //assert zero VAT for Switzerland
        $vatSelector = oxRegistry::get('oxVatSelector');
        $this->assertSame(0, $vatSelector->getUserVat($user));
        $this->assertSame(0, $vatSelector->getUserVat($user, true)); //no cache

        //Change to german address
        $this->changeUserAddressToGermany();

        //verify that the active user was updated
        $user = oxNew('oxUser');
        $user->loadActiveUser();
        $this->assertSame(self::GERMANY_COUNTRY_ID, $user->oxuser__oxcountryid->value);

        //verify german VAT
        $vatSelector = oxRegistry::get('oxVatSelector');
        $this->assertFalse($vatSelector->getUserVat($user, true));
        $this->assertFalse($vatSelector->getUserVat($user));

        $this->changeUserAddressToAustria();

        //verify that the active user was updated
        $user = oxNew('oxUser');
        $user->loadActiveUser();
        $this->assertSame(self::AUSTRIA_COUNTRY_ID, $user->oxuser__oxcountryid->value);

        //verify Austria VAT
        $vatSelector = oxRegistry::get('oxVatSelector');
        $this->assertSame(0, (int)$vatSelector->getUserVat($user));
        $this->assertSame(0, (int)$vatSelector->getUserVat($user, true)); //no cache
    }

    /**
     * Test basket calculation when user country changes during checkout.
     * Test case when we explicitly set user via oxBasket::setBasketUser.
     */
    public function testBasketCalculationOnUserCountryChangeExplicitlySetBasketUser(): void
    {
        //no user logged in atm, create a basket
        $basket = oxNew('oxBasket');
        $basket->addToBasket(self::TEST_ARTICLE_ID, 1); //14 EUR
        $this->getSession()->setBasket($basket);

        //create user, as soon at it is set in session, it is available for basket as well
        $user = $this->createActiveUser(); //Switzerland user
        $basket->setBasketUser($user);

        //verify basket calculation results
        $basket->calculateBasket(true);
        $this->assertSame(11.76, $basket->getNettoSum());
        $this->assertSame(11.76, $basket->getBruttoSum()); //no VAT for Switzerland

        //Change to german address
        $this->changeUserAddressToGermany();

        //verify that the basket user is up to date
        $basket = $this->getSession()->getBasket();
        $this->assertSame('Hahnentritt', $basket->getUser()->oxuser__oxlname->value);
        $this->assertSame('Hahnentritt', $basket->getBasketUser()->oxuser__oxlname->value);
        $basket->calculateBasket(true); //basket calculation triggers basket item user update

        //check basket calculation results, should now be with VAT due to german delivery address
        $this->assertSame(11.76, $basket->getNettoSum());
        $this->assertSame(14.0, $basket->getBruttoSum());
    }

    /**
     * Test basket calculation when user country changes during checkout.
     */
    public function testBasketCalculationOnUserCountryChange(): void
    {
        //no user logged in atm, create a basket
        $basket = oxNew('oxBasket');
        $basket->addToBasket(self::TEST_ARTICLE_ID, 1); //14 EUR
        $this->getSession()->setBasket($basket);

        //create user, as soon at it is set in session, it is available for basket as well
        $this->createActiveUser(); //Switzerland user

        //verify basket calculation results
        $basket->calculateBasket(true);
        $this->assertSame(11.76, $basket->getNettoSum());
        $this->assertSame(11.76, $basket->getBruttoSum()); //no VAT for Switzerland

        //Change to german address
        $this->changeUserAddressToGermany();

        //verify that the basket user is up to date
        $basket = $this->getSession()->getBasket();
        $basket->calculateBasket(true); //basket calculation triggers basket item user update

        //check basket calculation results, should now be with VAT due to german delivery address
        $this->assertSame(11.76, $basket->getNettoSum());
        $this->assertSame(14.0, $basket->getBruttoSum());
    }

    public function testVatCalculationWithDifferentCountries(): void
    {
        //no user logged in atm, create a basket
        $basket = oxNew('oxBasket');
        $basket->addToBasket(self::TEST_ARTICLE_ID, 1); //14 EUR
        $this->getSession()->setBasket($basket);
        //create user, as soon at it is set in session, it is available for basket as well
        $this->createActiveUser(); //Switzerland user
    }

    /**
     * Insert test user, set to session
     * @return oxUser
     */
    private function createActiveUser(): oxUser
    {
        $sTestUserId = substr_replace(oxRegistry::getUtilsObject()->generateUId(), '_', 0, 1);

        $user = oxNew('oxUser');
        $user->setId($sTestUserId);

        $user->oxuser__oxactive = new oxField('1', oxField::T_RAW);
        $user->oxuser__oxrights = new oxField('user', oxField::T_RAW);
        $user->oxuser__oxshopid = new oxField(ShopIdCalculator::BASE_SHOP_ID, oxField::T_RAW);
        $user->oxuser__oxusername = new oxField('testuser@oxideshop.dev', oxField::T_RAW);
        $user->oxuser__oxpassword = new oxField(
            'c630e7f6dd47f9ad60ece4492468149bfed3da3429940181464baae99941d0ffa5562' .
                                                'aaecd01eab71c4d886e5467c5fc4dd24a45819e125501f030f61b624d7d',
            oxField::T_RAW
        ); //password is asdfasdf
        $user->oxuser__oxpasssalt = new oxField('3ddda7c412dbd57325210968cd31ba86', oxField::T_RAW);
        $user->oxuser__oxcustnr = new oxField('667', oxField::T_RAW);
        $user->oxuser__oxfname = new oxField('Erna', oxField::T_RAW);
        $user->oxuser__oxlname = new oxField('Helvetia', oxField::T_RAW);
        $user->oxuser__oxstreet = new oxField('Dorfstrasse', oxField::T_RAW);
        $user->oxuser__oxstreetnr = new oxField('117', oxField::T_RAW);
        $user->oxuser__oxcity = new oxField('Oberbuchsiten', oxField::T_RAW);
        $user->oxuser__oxcountryid = new oxField(self::SWITZERLAND_COUNTRY_ID, oxField::T_RAW);
        $user->oxuser__oxzip = new oxField('4625', oxField::T_RAW);
        $user->oxuser__oxsal = new oxField('MRS', oxField::T_RAW);
        $user->oxuser__oxactive = new oxField('1', oxField::T_RAW);
        $user->oxuser__oxboni = new oxField('1000', oxField::T_RAW);
        $user->oxuser__oxcreate = new oxField('2015-05-20 22:10:51', oxField::T_RAW);
        $user->oxuser__oxregister = new oxField('2015-05-20 22:10:51', oxField::T_RAW);
        $user->oxuser__oxboni = new oxField('1000', oxField::T_RAW);

        $user->save();

        $this->getSession()->setVariable('usr', $user->getId());

        return $user;
    }

    /**
     * @return oxUser
     */
    private function createActiveBelgiumUser(): oxUser
    {
        $sTestUserId = substr_replace(oxRegistry::getUtilsObject()->generateUId(), '_', 0, 1);

        $user = oxNew('oxUser');
        $user->setId($sTestUserId);

        $user->oxuser__oxactive = new oxField('1', oxField::T_RAW);
        $user->oxuser__oxrights = new oxField('user', oxField::T_RAW);
        $user->oxuser__oxshopid = new oxField(ShopIdCalculator::BASE_SHOP_ID, oxField::T_RAW);
        $user->oxuser__oxusername = new oxField('testuser@oxideshop.dev', oxField::T_RAW);
        $user->oxuser__oxpassword = new oxField(
            'c630e7f6dd47f9ad60ece4492468149bfed3da3429940181464baae99941d0ffa5562' .
            'aaecd01eab71c4d886e5467c5fc4dd24a45819e125501f030f61b624d7d',
            oxField::T_RAW
        ); //password is asdfasdf
        $user->oxuser__oxpasssalt = new oxField('3ddda7c412dbd57325210968cd31ba86', oxField::T_RAW);
        $user->oxuser__oxcustnr = new oxField('667', oxField::T_RAW);
        $user->oxuser__oxfname = new oxField('Erna', oxField::T_RAW);
        $user->oxuser__oxlname = new oxField('Helvetia', oxField::T_RAW);
        $user->oxuser__oxstreet = new oxField('Dorfstrasse', oxField::T_RAW);
        $user->oxuser__oxstreetnr = new oxField('117', oxField::T_RAW);
        $user->oxuser__oxcity = new oxField('Oberbuchsiten', oxField::T_RAW);
        $user->oxuser__oxcountryid = new oxField(self::BELGIUM_COUNTRY_ID, oxField::T_RAW);
        $user->oxuser__oxzip = new oxField('4625', oxField::T_RAW);
        $user->oxuser__oxsal = new oxField('MRS', oxField::T_RAW);
        $user->oxuser__oxactive = new oxField('1', oxField::T_RAW);
        $user->oxuser__oxboni = new oxField('1000', oxField::T_RAW);
        $user->oxuser__oxcreate = new oxField('2015-05-20 22:10:51', oxField::T_RAW);
        $user->oxuser__oxregister = new oxField('2015-05-20 22:10:51', oxField::T_RAW);
        $user->oxuser__oxboni = new oxField('1000', oxField::T_RAW);

        $user->save();

        $this->getSession()->setVariable('usr', $user->getId());

        return $user;
    }

    private function changeUserAddressToGermany(): void
    {
        //now change the user address
        $rawValues = [
            'oxuser__oxfname'     => 'Erna',
            'oxuser__oxlname'     => 'Hahnentritt',
            'oxuser__oxstreetnr'  => '117',
            'oxuser__oxstreet'    => 'Landstrasse',
            'oxuser__oxzip'       => '22769',
            'oxuser__oxcity'      => 'Hamburg',
            'oxuser__oxcountryid' => self::GERMANY_COUNTRY_ID
       ];

        $this->setRequestParameter('invadr', $rawValues);
        $this->setRequestParameter('stoken', $this->getSession()->getSessionChallengeToken());

        $userComponent = oxNew('oxcmp_user');
        $this->assertSame('payment', $userComponent->changeUser());
    }

    private function changeUserAddressToAustria(): void
    {
        $rawValues = [
            'oxuser__oxfname'     => 'Erna',
            'oxuser__oxlname'     => 'Hahnentritt',
            'oxuser__oxstreetnr'  => '117',
            'oxuser__oxstreet'    => 'Landstrasse',
            'oxuser__oxzip'       => '3741',
            'oxuser__oxcity'      => 'PULKAU',
            'oxuser__oxcountryid' => self::AUSTRIA_COUNTRY_ID
        ];

        $this->setRequestParameter('invadr', $rawValues);
        $this->setRequestParameter('stoken', $this->getSession()->getSessionChallengeToken());

        $userComponent = oxNew('oxcmp_user');
        $this->assertSame('payment', $userComponent->changeUser());
    }
}
