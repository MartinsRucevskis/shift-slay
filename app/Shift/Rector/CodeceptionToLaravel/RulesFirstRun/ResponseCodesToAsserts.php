<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\LNumber;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

// Will generate diff as (something)->chainedCall(), but actually will be converted to something->chainedCall(), due to afterTraverse regex modification
class ResponseCodesToAsserts extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $this->isName($node->name, 'assertStatus')) {
            return null;
        }

        if (count($node->getArgs()) !== 1) {
            return null;
        }

        $arg = $node->getArgs()[0];
        $argValue = $arg->value;

        if (! $argValue instanceof LNumber) {
            return null;
        }
        $replacementMethod = match ($argValue->value) {
            200 => 'assertOk',
            201 => 'assertCreated',
            202 => 'assertAccepted',
            204 => 'assertNoContent',
            301 => 'assertMovedPermanently',
            302 => 'assertFound',
            304 => 'assertNotModified',
            307 => 'assertTemporaryRedirect',
            308 => 'assertPermanentRedirect',
            400 => 'assertBadRequest',
            401 => 'assertUnauthorized',
            402 => 'assertPaymentRequired',
            403 => 'assertForbidden',
            404 => 'assertNotFound',
            405 => 'assertMethodNotAllowed',
            406 => 'assertNotAcceptable',
            408 => 'assertRequestTimeout',
            409 => 'assertConflict',
            410 => 'assertGone',
            415 => 'assertUnsupportedMediaType',
            422 => 'assertUnprocessable',
            429 => 'assertTooManyRequests',
            500 => 'assertInternalServerError',
            503 => 'assertServiceUnavailable',
            default => null
        };

        if ($replacementMethod === null) {
            return null;
        }

        $node->name = new Identifier($replacementMethod);
        $node->args = [];

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Improve asserts, by changing status code asserts to inbuilt methods', [
            new CodeSample(

                '$this->getJson()->assertStatus(200)',

                '$this->getJson()->assertOk()'
            ),
        ]);
    }
}
