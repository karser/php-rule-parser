<?php declare(strict_types=1);

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rule\tests\integration;

use nicoSWD\Rule;
use PHPUnit\Framework\TestCase;

final class RuleTest extends TestCase
{
    /** @test */
    public function basicRuleWithCommentsEvaluatesCorrectly(): void
    {
        $string = '
            /**
             * This is a test rule with comments
             */

             // This is true
             2 < 3 && (
                // this is false, because foo does not equal 4
                foo == 4
                // but bar is greater than 6
                || bar > 6
             )';

        $vars = [
            'foo' => 5,
            'bar' => 7,
        ];

        $rule = new Rule\Rule($string, $vars);

        $this->assertTrue($rule->isTrue());
        $this->assertTrue(!$rule->isFalse());
    }

    /** @test */
    public function variableCallback()
    {
        $string = 'foo.bar === 10 && bar.foo === 5 && foo.bar > bar.foo2 && a === 10';
        $map = [
            'foo.bar' => 10,
            'bar.foo' => 5,
            'bar.foo2' => 1,
            'a' => 10,
        ];
        $rule = new Rule\Rule($string, [], function (string $name) use ($map) {
            return $map[$name];
        });
        $this->assertTrue($rule->isTrue());
    }

    /** @test */
    public function stringVariableCallback()
    {
        $string = 'foo.bar === "a@b.c" && bar.foo === \'https://ab.c\' ';
        $map = [
            'foo.bar' => 'a@b.c',
            'bar.foo' => 'https://ab.c',
        ];
        $rule = new Rule\Rule($string, [], function (string $name) use ($map) {
            return $map[$name];
        });
        $this->assertTrue($rule->isTrue());
    }

    /** @test */
    public function isValidReturnsFalseOnInvalidSyntax(): void
    {
        $ruleStr = '(2 == 2) && (1 < 3 && 3 > 2 (1 == 1))';

        $rule = new Rule\Rule($ruleStr);

        $this->assertFalse($rule->isValid());
        $this->assertSame('Unexpected "(" at position 28', $rule->getError());
    }

    /** @test */
    public function isValidReturnsTrueOnValidSyntax(): void
    {
        $ruleStr = '(2 == 2) && (1 < 3 && 3 > 2 || (1 == 1))';

        $rule = new Rule\Rule($ruleStr);

        $this->assertTrue($rule->isValid());
        $this->assertEmpty($rule->getError());
        $this->assertTrue($rule->isTrue());
    }

    /** @test */
    public function endWith(): void
    {
        $ruleStr = 'foo.bar.endsWith("foo") === true';
        $map = [
            'foo.bar' => 'something ends with foo',
        ];
        $rule = new Rule\Rule($ruleStr,  [], function (string $name) use ($map) {
            return $map[$name];
        });
        $this->assertTrue($rule->isTrue());
    }

    /** @test */
    public function startWith(): void
    {
        $ruleStr = 'foo.bar.startsWith("foo") === true';
        $map = [
            'foo.bar' => 'foo at the start',
        ];
        $rule = new Rule\Rule($ruleStr,  [], function (string $name) use ($map) {
            return $map[$name];
        });
        $this->assertTrue($rule->isTrue());
    }

    /** @test */
    public function indexOf(): void
    {
        $ruleStr = 'foo.bar.indexOf("foo") !== -1';
        $map = [
            'foo.bar' => 'string with foo in it',
        ];
        $rule = new Rule\Rule($ruleStr,  [], function (string $name) use ($map) {
            return $map[$name];
        });
        $this->assertTrue($rule->isTrue());
    }

    /** @test */
    public function inArray(): void
    {
        $ruleStr = '"foo" in foo.bar';
        $map = [
            'foo.bar' => ['test', 'foo'],
        ];
        $rule = new Rule\Rule($ruleStr,  [], function (string $name) use ($map) {
            return $map[$name];
        });
        $this->assertTrue($rule->isTrue());
    }

    /** @test */
    public function logicOperators(): void
    {
        $ruleStr = 'foo.bar >= 1 && bar.foo >= 1 && foo.bar <= 1 && foo.n === null && foo.s !== null && foo.a1 === [] && foo.a2 !== [] && foo.s === "foo" && foo.bar !== "foo"';
        $map = [
            'foo.bar' => 1,
            'bar.foo' => 2,
            'foo.n' => null,
            'foo.a1' => [],
            'foo.a2' => ['one','two'],
            'foo.s' => 'foo'
        ];
        $rule = new Rule\Rule($ruleStr,  [], function (string $name) use ($map) {
            return $map[$name];
        });
        $this->assertTrue($rule->isTrue());
    }

    /** @test */
    public function datesCompare(): void
    {
        $ruleStr = 'foo.bar >= "2020-02-01 00:00:00" && foo.bar <= "2020-02-01 23:59:59" && bar.foo >= "2020-02-01 00:00:00" && bar.foo <= "2020-02-01 23:59:59" && foo.bar < "2020-02-01 23:59:59" && bar.foo > "2020-02-01 00:00:00"';
        $map = [
            'foo.bar' => '2020-02-01 00:00:00',
            'bar.foo' => '2020-02-01 23:59:59',
        ];
        $rule = new Rule\Rule($ruleStr,  [], function (string $name) use ($map) {
            return $map[$name];
        });
        $this->assertTrue($rule->isTrue());
    }
}
