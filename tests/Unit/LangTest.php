<?php

namespace Javaabu\PostTypes\Tests\Unit;

use Javaabu\PostTypes\Status;
use Javaabu\PostTypes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LangTest extends TestCase
{
    /** Strings in docblock examples, not shown by the package */
    protected const array EXAMPLES = [];

    /**
     * @return array<int, string>
     */
    protected function usedStrings(): array
    {
        $root = dirname(__DIR__, 2);
        $strings = [];

        foreach (['src', 'resources/views'] as $folder) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$folder}")) as $file) {
                if (! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }

                preg_match_all("/(?:__|trans_choice)\\('((?:[^'\\\\]|\\\\.)*)'/", file_get_contents($file->getPathname()), $matches);

                foreach ($matches[1] as $string) {
                    $strings[] = stripslashes($string);
                }
            }
        }

        return array_values(array_diff(array_unique($strings), self::EXAMPLES));
    }

    /**
     * @return array<string, string>
     */
    protected function lang(string $locale): array
    {
        return json_decode(file_get_contents(dirname(__DIR__, 2) . "/lang/{$locale}.json"), true);
    }

    #[Test]
    public function it_has_every_string_in_english_and_dhivehi(): void
    {
        $en = $this->lang('en');
        $dv = $this->lang('dv');

        foreach ($this->usedStrings() as $string) {
            $this->assertArrayHasKey($string, $en, "lang/en.json is missing \"{$string}\"");
            $this->assertArrayHasKey($string, $dv, "lang/dv.json is missing \"{$string}\"");
        }

        $this->assertEquals(array_keys($en), array_keys($dv));
    }

    #[Test]
    public function it_keeps_the_placeholders_in_dhivehi(): void
    {
        foreach ($this->lang('dv') as $key => $translation) {
            preg_match_all('/:[a-z_]+/', $key, $expected);
            preg_match_all('/:[a-z_]+/', $translation, $actual);

            sort($expected[0]);
            sort($actual[0]);

            $this->assertEquals(array_unique($expected[0]), array_unique($actual[0]), "Placeholders differ in \"{$key}\"");
            $this->assertMatchesRegularExpression('/\p{Thaana}/u', $translation, "\"{$key}\" isn't in Thaana");
        }
    }

    #[Test]
    public function it_translates_the_built_in_statuses_and_pages(): void
    {
        $this->app->setLocale('dv');

        $this->assertSame('ޝާއިޢުކުރެވިފައި', Status::defaults()['published']->getLabel());
        $this->assertSame('ރިވިއުއަށް މަޑުކުރަނީ', Status::defaults()['pending']->getLabel());
        $this->assertSame('3 ޕޯސްޓް ޝާއިޢުކުރެވިއްޖެ.', trans_choice('{1} :count post published.|[0,*] :count posts published.', 3, ['count' => 3]));
    }
}
