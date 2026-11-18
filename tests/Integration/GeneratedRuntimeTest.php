<?php

declare(strict_types=1);

namespace {
    if (! function_exists('fixture_get_option')) {
        /** @var array<string, mixed> $GLOBALS['fixture_options'] */
        $GLOBALS['fixture_options'] = [];

        function fixture_get_option(string $key, mixed $default = null): mixed
        {
            return $GLOBALS['fixture_options'][$key] ?? $default;
        }
    }

    if (! function_exists('__')) {
        function __(string $text, string $domain = 'default'): string
        {
            return "[{$domain}] {$text}";
        }
    }
}

namespace Fixture {
    final class SettingsStore
    {
        /** @param array<string, mixed> $values */
        public function __construct(private readonly array $values)
        {
        }

        public function get(string $key): mixed
        {
            return $this->values[$key] ?? null;
        }
    }
}

namespace App {
    final class Calendar
    {
        /** @return array<string, string> */
        public static function weekdayOptions(): array
        {
            return ['mon' => 'Monday'];
        }
    }
}

namespace Tikhomirov\SettingsCodegen\Tests\Integration {
    use PHPUnit\Framework\TestCase;
    use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

    /**
     * Executes the golden files: they must not only look right but behave right.
     */
    final class GeneratedRuntimeTest extends TestCase
    {
        private const EXPECTED = __DIR__ . '/../Fixtures/expected';

        protected function setUp(): void
        {
            if (PHP_VERSION_ID < 80200) {
                $this->markTestSkipped('Golden files use readonly classes (PHP 8.2+).');
            }
        }

        public static function setUpBeforeClass(): void
        {
            if (PHP_VERSION_ID < 80200) {
                return;
            }

            foreach (['wordpress', 'laravel', 'symfony', 'yii3', 'custom'] as $dir) {
                foreach (glob(self::EXPECTED . "/{$dir}/*/*.php") ?: [] as $file) {
                    require_once $file;
                }
                foreach (glob(self::EXPECTED . "/{$dir}/*.php") ?: [] as $file) {
                    if (basename($file) !== 'registry.php') {
                        require_once $file;
                    }
                }
            }
        }

        public function testWordPressConfigReadsOptionsWithDefaults(): void
        {
            $GLOBALS['fixture_options'] = ['enable_calendar' => 'no', 'slot_minutes' => '45'];
            $config = \Fixture\WordPress\Delivery\DeliveryConfig::load();

            $this->assertFalse($config->enableCalendar);
            $this->assertSame(45, $config->slotMinutes);
            $this->assertSame('00:30', $config->restTime);
            $this->assertSame(1.5, $config->surcharge);
            $this->assertSame(['mon', 'tue', 'wed'], $config->weekdays);
            $this->assertSame('', $config->internalToken);
            $this->assertFalse($config->toArray()['enable_calendar']);

            $GLOBALS['fixture_options'] = [];
            $this->assertTrue(\Fixture\WordPress\Delivery\DeliveryConfig::load()->enableCalendar);
        }

        public function testWordPressUiSchema(): void
        {
            $fields = \Fixture\WordPress\Delivery\DeliveryUiSchema::getFields();

            $this->assertSame('[fixture] Enable delivery calendar', $fields[1]['title']);
            $this->assertSame('[fixture] Delivery calendar and slots. Docs: <a href="https://example.com">example.com</a>', $fields[0]['desc']);
            $this->assertSame(['mon' => 'Monday'], $fields[6]['options']);
            $this->assertArrayNotHasKey('rules', $fields[1]);
            $this->assertArrayNotHasKey('modes', $fields[0], 'extension keys stay out of the UI');
            $this->assertNotContains('shop_internal_token', array_column($fields, 'id'), 'ui: false is hidden');
            $this->assertContains('shop_internal_token', \Fixture\WordPress\Delivery\DeliveryUiSchema::getAllOptionKeys());
            $this->assertSame('shop_rest_time', \Fixture\WordPress\Delivery\DeliveryUiSchema::optionIdFor('restTime'));

            $this->expectException(\InvalidArgumentException::class);
            \Fixture\WordPress\Notes\NotesUiSchema::optionIdFor('missing');
        }

        public function testWordPressAttributesFromExtension(): void
        {
            $class = new \ReflectionClass(\Fixture\WordPress\Delivery\DeliveryConfig::class);
            $this->assertSame(['modes' => ['delivery']], $class->getAttributes()[0]->getArguments());

            $property = $class->getProperty('restTime');
            $this->assertSame('Fixture\\Attribute\\ModeDefaults', $property->getAttributes()[0]->getName());
            $this->assertSame(['values' => ['pickup' => '00:15']], $property->getAttributes()[0]->getArguments());
        }

        public function testLaravelFromArrayAndDefinition(): void
        {
            $config = \Fixture\Laravel\Delivery\DeliveryConfig::fromArray(['enable_calendar' => 'false', 'surcharge' => '2.25', 'weekdays' => 'oops']);

            $this->assertFalse($config->enableCalendar);
            $this->assertSame(2.25, $config->surcharge);
            $this->assertSame(['mon', 'tue', 'wed'], $config->weekdays, 'invalid values fall back to defaults');
            $this->assertSame(30, $config->slotMinutes);
            $this->assertSame(['required', 'regex:/^\d{2}:\d{2}$/'], \Fixture\Laravel\Delivery\DeliveryDefinition::rules()['rest_time']);
            $this->assertSame(true, \Fixture\Laravel\Delivery\DeliveryDefinition::defaults()['enable_calendar']);
            $this->assertSame('rest_time', \Fixture\Laravel\Delivery\DeliveryDefinition::keys()['restTime']);
            $this->assertCount(10, \Fixture\Laravel\Delivery\DeliveryDefinition::fields());
            $this->assertEquals(
                \Fixture\Laravel\Delivery\DeliveryConfig::fromArray(\Fixture\Laravel\Delivery\DeliveryDefinition::defaults()),
                \Fixture\Laravel\Delivery\DeliveryConfig::fromArray([]),
            );
        }

        public function testSymfonyLoadsParameters(): void
        {
            $config = \Fixture\Symfony\Delivery\DeliveryConfig::load(new ParameterBag([
                'app.settings.rest_time' => '01:00',
                'app.settings.slot_minutes' => 15,
            ]));

            $this->assertSame('01:00', $config->restTime);
            $this->assertSame(15, $config->slotMinutes);
            $this->assertTrue($config->enableCalendar);
        }

        public function testYii3LoadsNestedParams(): void
        {
            $config = \Fixture\Yii3\Delivery\DeliveryConfig::load([
                'app' => ['settings' => ['mode' => 'range', 'excluded' => [7, 8]]],
            ]);

            $this->assertSame('range', $config->mode);
            $this->assertSame([7, 8], $config->excluded);
            $this->assertSame('00:30', $config->restTime);
            $this->assertSame([], \Fixture\Yii3\Notes\NotesConfig::load([])->toArray());
        }

        public function testCustomReader(): void
        {
            $config = \Fixture\Custom\DeliveryConfig::load(new \Fixture\SettingsStore(['mode' => 'range', 'enable_calendar' => '0']));

            $this->assertSame('range', $config->mode);
            $this->assertFalse($config->enableCalendar);
        }
    }
}
