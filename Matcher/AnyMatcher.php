<?php
declare(strict_types=1);
namespace Viserio\Component\Routing\Matcher;

class AnyMatcher extends AbstractMatcher
{
    /**
     * Create a new any matcher instance.
     *
     * @param array $parameterKeys
     */
    public function __construct(array $parameterKeys)
    {
        $this->parameterKeys = $parameterKeys;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionExpression(string $segmentVariable, int $uniqueKey = null): string
    {
        return $segmentVariable . ' !== \'\'';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMatchHash(): string
    {
        return '';
    }
}