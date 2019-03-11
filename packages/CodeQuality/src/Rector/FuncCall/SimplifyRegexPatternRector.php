<?php declare(strict_types=1);

namespace Rector\CodeQuality\Rector\FuncCall;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use Rector\Php\Regex\RegexPatternArgumentManipulator;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;

/**
 * @see http://php.net/manual/en/function.preg-match.php#105924
 */
final class SimplifyRegexPatternRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private $complexPatternToSimple = [
        '[0-9]' => '\d',
        '[a-zA-Z0-9_]' => '\w',
        '[\r\n\t\f\v ]' => '\s',
    ];

    /**
     * @var RegexPatternArgumentManipulator
     */
    private $regexPatternArgumentManipulator;

    public function __construct(RegexPatternArgumentManipulator $regexPatternArgumentManipulator)
    {
        $this->regexPatternArgumentManipulator = $regexPatternArgumentManipulator;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Simplify regex pattern to known ranges', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($value)
    {
        preg_match('#[a-zA-Z0-9+]#', $value);
    }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($value)
    {
        preg_match('#[\w\d+]#', $value);
    }
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        $pattern = $this->regexPatternArgumentManipulator->matchCallArgumentWithRegexPattern($node);
        if (! $pattern instanceof String_) {
            return null;
        }

        foreach ($this->complexPatternToSimple as $complexPattern => $simple) {
            $pattern->value = Strings::replace($pattern->value, '#' . preg_quote($complexPattern) . '#', $simple);
        }

        return $node;
    }
}
