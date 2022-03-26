<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\SystemConfiguration;
use App\Entity\Configuration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\SystemConfiguration
 */
class SystemConfigurationTest extends TestCase
{
    /**
     * @param array $settings
     * @param array $loaderSettings
     * @return SystemConfiguration
     */
    protected function getSut(array $settings, array $loaderSettings = [])
    {
        $loader = new TestConfigLoader($loaderSettings);

        return new SystemConfiguration($loader, $settings);
    }

    protected function getDefaultSettings()
    {
        return [
            'timesheet' => [
                'rules' => [
                    'allow_future_times' => false,
                    'lockdown_period_start' => null,
                    'lockdown_period_end' => null,
                    'lockdown_grace_period' => null,
                ],
                'mode' => 'duration_only',
                'markdown_content' => false,
                'active_entries' => [
                    'hard_limit' => 99,
                ],
                'default_begin' => 'now',
                'duration_increment' => 10,
                'time_increment' => 5,
            ],
            'defaults' => [
                'customer' => [
                    'timezone' => 'Europe/London',
                    'currency' => 'GBP',
                    'country' => 'FR',
                ],
                'user' => [
                    'timezone' => 'foo/bar',
                    'theme' => 'blue',
                    'language' => 'IT',
                    'currency' => 'USD',
                ],
            ],
            'calendar' => [
                'businessHours' => [
                    'days' => [2, 4, 6],
                    'begin' => '07:49',
                    'end' => '19:27'
                ],
                'day_limit' => 20,
                'slot_duration' => '01:11:00',
                'week_numbers' => false,
                'visibleHours' => [
                    'begin' => '06:00:00',
                    'end' => '21:00:43',
                ],
                'google' => [
                    'api_key' => 'wertwertwegsdfbdf243w567fg8ihuon',
                    'sources' => [
                        'holidays' => [
                            'id' => 'de.german#holiday@group.v.calendar.google.com',
                            'color' => '#ccc',
                        ],
                        'holidays_en' => [
                            'id' => 'en.german#holiday@group.v.calendar.google.com',
                            'color' => '#fff',
                        ],
                    ]
                ],
                'weekends' => true,
            ],
            'saml' => [
                'activate' => false,
                'title' => 'Fantastic OAuth login'
            ],
            'theme' => [
                'color_choices' => 'Maroon|#800000,Brown|#a52a2a,Red|#ff0000,Orange|#ffa500,#ffffff,,|#000000',
                'colors_limited' => true,
                'branding' => [
                    'logo' => null,
                    'mini' => null,
                    'company' => 'Acme Corp.',
                    'title' => 'Fantastic Time-Tracking',
                    'translation' => null,
                ],
            ],
        ];
    }

    protected function getDefaultLoaderSettings()
    {
        return [
            (new Configuration())->setName('defaults.customer.timezone')->setValue('Russia/Moscov'),
            (new Configuration())->setName('defaults.customer.currency')->setValue('RUB'),
            (new Configuration())->setName('calendar.slot_duration')->setValue('00:30:00'),
            (new Configuration())->setName('timesheet.rules.allow_future_times')->setValue('1'),
            (new Configuration())->setName('timesheet.rules.lockdown_period_start')->setValue('first day of last month'),
            (new Configuration())->setName('timesheet.rules.lockdown_period_end')->setValue('last day of last month'),
            (new Configuration())->setName('timesheet.rules.lockdown_grace_period')->setValue('+5 days'),
            (new Configuration())->setName('timesheet.mode')->setValue('default'),
            (new Configuration())->setName('timesheet.markdown_content')->setValue('1'),
            (new Configuration())->setName('timesheet.default_begin')->setValue('07:00'),
            (new Configuration())->setName('timesheet.active_entries.hard_limit')->setValue('7'),
            (new Configuration())->setName('theme.colors_limited')->setValue(false),
        ];
    }

    public function testPrefix()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals('kimai', $sut->getPrefix());
    }

    public function testDefaultWithoutLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals('Europe/London', $sut->find('defaults.customer.timezone'));
        $this->assertEquals('GBP', $sut->find('defaults.customer.currency'));
        $this->assertFalse($sut->find('timesheet.rules.allow_future_times'));
        $this->assertEquals(99, $sut->find('timesheet.active_entries.hard_limit'));
        $this->assertTrue($sut->find('theme.colors_limited'));
        $this->assertTrue($sut->isThemeColorsLimited());
        $this->assertEquals('Maroon|#800000,Brown|#a52a2a,Red|#ff0000,Orange|#ffa500,#ffffff,,|#000000', $sut->getThemeColorChoices());
        $this->assertEquals('Fantastic Time-Tracking', $sut->getBrandingTitle());
    }

    public function testDefaultWithLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), $this->getDefaultLoaderSettings());
        $this->assertEquals('Russia/Moscov', $sut->find('defaults.customer.timezone'));
        $this->assertEquals('RUB', $sut->find('defaults.customer.currency'));
        $this->assertTrue($sut->find('timesheet.rules.allow_future_times'));
        $this->assertEquals(7, $sut->find('timesheet.active_entries.hard_limit'));
        $this->assertFalse($sut->isSamlActive());
        $this->assertFalse($sut->find('theme.colors_limited'));
        $this->assertEquals('Europe/London', $sut->default('defaults.customer.timezone'));
    }

    public function testDefaultWithMixedConfigs()
    {
        $sut = $this->getSut($this->getDefaultSettings(), [
            (new Configuration())->setName('timesheet.rules.allow_future_times')->setValue(''),
            (new Configuration())->setName('saml.activate')->setValue(true),
            (new Configuration())->setName('theme.color_choices')->setValue(''),
            (new Configuration())->setName('company.financial_year')->setValue('2020-03-27'),
        ]);
        $this->assertFalse($sut->find('timesheet.rules.allow_future_times'));
        $this->assertTrue($sut->isSamlActive());
        $this->assertEquals('Maroon|#800000,Brown|#a52a2a,Red|#ff0000,Orange|#ffa500,#ffffff,,|#000000', $sut->getThemeColorChoices());
        $this->assertEquals('2020-03-27', $sut->getFinancialYearStart());
    }

    public function testOffsetUnsetThrowsException()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('SystemBundleConfiguration does not support offsetUnset()');

        $sut = $this->getSut($this->getDefaultSettings(), []);
        $sut->offsetUnset('dfsdf');
    }

    public function testUnknownConfigs()
    {
        $sut = $this->getSut($this->getDefaultSettings(), [
            (new Configuration())->setName('timesheet.foo')->setValue('hello'),
        ]);
        $this->assertEquals('hello', $sut->find('timesheet.foo'));
        $this->assertEquals('hello', $sut->offsetGet('timesheet.foo'));
        $this->assertTrue($sut->has('timesheet.foo'));
        $this->assertTrue($sut->offsetExists('timesheet.foo'));
        $this->assertFalse($sut->has('timesheet.yyyyyyyyy'));
        $this->assertFalse($sut->offsetExists('timesheet.yyyyyyyyy'));
        $this->assertFalse($sut->has('xxxxxxxx.yyyyyyyyy'));
        $this->assertFalse($sut->offsetExists('xxxxxxxx.yyyyyyyyy'));
        $this->assertNull($sut->find('xxxxxxxx.yyyyyyyyy'));
        $this->assertNull($sut->offsetGet('xxxxxxxx.yyyyyyyyy'));

        $sut->offsetSet('xxxxxxxx.yyyyyyyyy', 'foooo-bar!');
        $this->assertTrue($sut->has('xxxxxxxx.yyyyyyyyy'));
        $this->assertTrue($sut->offsetExists('xxxxxxxx.yyyyyyyyy'));
        $this->assertEquals('foooo-bar!', $sut->find('xxxxxxxx.yyyyyyyyy'));
        $this->assertEquals('foooo-bar!', $sut->offsetGet('xxxxxxxx.yyyyyyyyy'));
    }

    public function testCalendarWithoutLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals([2, 4, 6], $sut->getCalendarBusinessDays());
        $this->assertEquals('07:49', $sut->getCalendarBusinessTimeBegin());
        $this->assertEquals('19:27', $sut->getCalendarBusinessTimeEnd());
        $this->assertEquals('06:00:00', $sut->getCalendarTimeframeBegin());
        $this->assertEquals('21:00:43', $sut->getCalendarTimeframeEnd());
        $this->assertEquals('01:11:00', $sut->getCalendarSlotDuration());
        $this->assertEquals(20, $sut->getCalendarDayLimit());
        $this->assertFalse($sut->isCalendarShowWeekNumbers());
        $this->assertTrue($sut->isCalendarShowWeekends());

        $this->assertEquals('wertwertwegsdfbdf243w567fg8ihuon', $sut->getCalendarGoogleApiKey());
        $sources = $sut->getCalendarGoogleSources();
        $this->assertEquals(2, \count($sources));
    }

    public function testCalendarWithLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), $this->getDefaultLoaderSettings());
        $this->assertEquals('00:30:00', $sut->getCalendarSlotDuration());
        $sources = $sut->getCalendarGoogleSources();
        $this->assertEquals(2, \count($sources));
    }

    public function testFormDefaultWithoutLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals('Europe/London', $sut->getCustomerDefaultTimezone());
        $this->assertEquals('GBP', $sut->getCustomerDefaultCurrency());
        $this->assertEquals('FR', $sut->getCustomerDefaultCountry());
        $this->assertEquals('foo/bar', $sut->getUserDefaultTimezone());
        $this->assertEquals('blue', $sut->getUserDefaultTheme());
        $this->assertEquals('IT', $sut->getUserDefaultLanguage());
        $this->assertEquals('USD', $sut->getUserDefaultCurrency());
        $this->assertNull($sut->getFinancialYearStart());
    }

    public function testFormDefaultWithLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), $this->getDefaultLoaderSettings());
        $this->assertEquals('Russia/Moscov', $sut->getCustomerDefaultTimezone());
        $this->assertEquals('RUB', $sut->getCustomerDefaultCurrency());
        $this->assertEquals('FR', $sut->getCustomerDefaultCountry());
        $this->assertEquals('foo/bar', $sut->getUserDefaultTimezone());
        $this->assertEquals('blue', $sut->getUserDefaultTheme());
        $this->assertEquals('IT', $sut->getUserDefaultLanguage());
        $this->assertEquals('USD', $sut->getUserDefaultCurrency());
    }

    public function testTimesheetWithoutLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), []);
        $this->assertEquals(99, $sut->getTimesheetActiveEntriesHardLimit());
        $this->assertFalse($sut->isTimesheetAllowFutureTimes());
        $this->assertFalse($sut->isTimesheetMarkdownEnabled());
        $this->assertEquals('duration_only', $sut->getTimesheetTrackingMode());
        $this->assertEquals('now', $sut->getTimesheetDefaultBeginTime());
        $this->assertFalse($sut->isTimesheetLockdownActive());
        $this->assertEquals('', $sut->getTimesheetLockdownPeriodStart());
        $this->assertEquals('', $sut->getTimesheetLockdownPeriodEnd());
        $this->assertEquals('', $sut->getTimesheetLockdownGracePeriod());
        $this->assertEquals('', $sut->isTimesheetAllowOverlappingRecords());
        $this->assertEquals('', $sut->getTimesheetDefaultRoundingDays());
        $this->assertEquals('', $sut->getTimesheetDefaultRoundingMode());
        $this->assertEquals(0, $sut->getTimesheetDefaultRoundingDuration());
        $this->assertEquals(0, $sut->getTimesheetDefaultRoundingEnd());
        $this->assertEquals(0, $sut->getTimesheetDefaultRoundingBegin());
        $this->assertEquals(10, $sut->getTimesheetIncrementDuration());
        $this->assertEquals(5, $sut->getTimesheetIncrementBegin());
        $this->assertEquals(5, $sut->getTimesheetIncrementEnd());
    }

    public function testTimesheetWithLoader()
    {
        $sut = $this->getSut($this->getDefaultSettings(), $this->getDefaultLoaderSettings());
        $this->assertEquals(7, $sut->getTimesheetActiveEntriesHardLimit());
        $this->assertTrue($sut->isTimesheetAllowFutureTimes());
        $this->assertTrue($sut->isTimesheetMarkdownEnabled());
        $this->assertEquals('default', $sut->getTimesheetTrackingMode());
        $this->assertEquals('07:00', $sut->getTimesheetDefaultBeginTime());
        $this->assertTrue($sut->isTimesheetLockdownActive());
        $this->assertEquals('first day of last month', $sut->getTimesheetLockdownPeriodStart());
        $this->assertEquals('last day of last month', $sut->getTimesheetLockdownPeriodEnd());
        $this->assertEquals('+5 days', $sut->getTimesheetLockdownGracePeriod());
        $this->assertEquals('', $sut->isTimesheetAllowOverlappingRecords());
        $this->assertEquals('', $sut->getTimesheetDefaultRoundingDays());
        $this->assertEquals('', $sut->getTimesheetDefaultRoundingMode());
        $this->assertEquals(0, $sut->getTimesheetDefaultRoundingDuration());
        $this->assertEquals(0, $sut->getTimesheetDefaultRoundingEnd());
        $this->assertEquals(0, $sut->getTimesheetDefaultRoundingBegin());
        $this->assertEquals(10, $sut->getTimesheetIncrementDuration());
        $this->assertEquals(5, $sut->getTimesheetIncrementBegin());
        $this->assertEquals(5, $sut->getTimesheetIncrementEnd());
    }
}
