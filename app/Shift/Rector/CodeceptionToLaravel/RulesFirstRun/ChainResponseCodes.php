<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

// Will generate diff as (something)->chainedCall(), but actually will be converted to something->chainedCall(), due to afterTraverse regex modification
class ChainResponseCodes extends AbstractRector
{
    private array $sendMethods = ['sendGET', 'sendPOST', 'sendPATCH', 'sendDELETE', 'getJson', 'postJson', 'patchJson', 'deleteJson', 'sendGet', 'sendPatch', 'sendDelete', 'sendPost'];

    private array $codes = [
        'CONTINUE' => 100,
        'PROCESSING' => 102,
        'EARLY_HINTS' => 103,
        'OK' => 200,
        'CREATED' => 201,
        'ACCEPTED' => 202,
        'NON_AUTHORITATIVE_INFORMATION' => 203,
        'NO_CONTENT' => 204,
        'RESET_CONTENT' => 205,
        'PARTIAL_CONTENT' => 206,
        'MULTI_STATUS' => 207,
        'ALREADY_REPORTED' => 208,
        'IM_USED' => 226,
        'MULTIPLE_CHOICES' => 300,
        'MOVED_PERMANENTLY' => 301,
        'FOUND' => 302,
        'SEE_OTHER' => 303,
        'NOT_MODIFIED' => 304,
        'USE_PROXY' => 305,
        'RESERVED' => 306,
        'TEMPORARY_REDIRECT' => 307,
        'PERMANENT_REDIRECT' => 308,
        'BAD_REQUEST' => 400,
        'UNAUTHORIZED' => 401,
        'PAYMENT_REQUIRED' => 402,
        'FORBIDDEN' => 403,
        'NOT_FOUND' => 404,
        'METHOD_NOT_ALLOWED' => 405,
        'NOT_ACCEPTABLE' => 406,
        'PROXY_AUTHENTICATION_REQUIRED' => 407,
        'REQUEST_TIMEOUT' => 408,
        'CONFLICT' => 409,
        'GONE' => 410,
        'LENGTH_REQUIRED' => 411,
        'PRECONDITION_FAILED' => 412,
        'REQUEST_ENTITY_TOO_LARGE' => 413,
        'REQUEST_URI_TOO_LONG' => 414,
        'UNSUPPORTED_MEDIA_TYPE' => 415,
        'REQUESTED_RANGE_NOT_SATISFIABLE' => 416,
        'EXPECTATION_FAILED' => 417,
        'UNASSIGNED' => 418,
        'MISDIRECTED_REQUEST' => 421,
        'UNPROCESSABLE_ENTITY' => 422,
        'LOCKED' => 423,
        'FAILED_DEPENDENCY' => 424,
        'TOO_EARLY' => 425,
        'UPGRADE_REQUIRED' => 426,
        'PRECONDITION_REQUIRED' => 428,
        'TOO_MANY_REQUESTS' => 429,
        'REQUEST_HEADER_FIELDS_TOO_LARGE' => 431,
        'UNAVAILABLE_FOR_LEGAL_REASONS' => 451,
        'INTERNAL_SERVER_ERROR' => 500,
        'NOT_IMPLEMENTED' => 501,
        'BAD_GATEWAY' => 502,
        'SERVICE_UNAVAILABLE' => 503,
        'GATEWAY_TIMEOUT' => 504,
        'HTTP_VERSION_NOT_SUPPORTED' => 505,
        'VARIANT_ALSO_NEGOTIATES' => 506,
        'INSUFFICIENT_STORAGE' => 507,
        'LOOP_DETECTED' => 508,
        'NOT_EXTENDED' => 510,
        'NETWORK_AUTHENTICATION_REQUIRED' => 511,
    ];

    public function getNodeTypes(): array
    {
        return [Node\Stmt\ClassMethod::class];
    }

    public function refactor(Node $node): ?Node
    {
        $methodStmts = array_filter($node->stmts ?? [], function ($stmt) {
            return isset($stmt->expr) && $stmt->expr instanceof MethodCall;
        });
        $sendMethod = null;
        /** @var MethodCall $stmnt */
        foreach ($methodStmts as $key => $stmnt) {
            if (in_array($stmnt->expr?->name?->name, $this->sendMethods)) {
                $sendMethod = $key;
            }
            if ($stmnt->expr?->name?->name === 'seeResponseCodeIs') {
                if (isset($sendMethod)) {
                    if ($stmnt->expr->args[0]->value instanceof Node\Scalar\LNumber) {
                        $code = $stmnt->expr->args[0]->value->value;
                    } else {
                        $code = $this->codes[$stmnt->expr->args[0]->value->name->name];
                    }
                    $node->stmts[$sendMethod]->expr = new \PhpParser\Node\Expr\MethodCall($node->stmts[$sendMethod]->expr, 'assertStatus', [new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\LNumber($code))]);
                    unset($node->stmts[$key]);
                }
                $sendMethod = null;
            }
        }

        return $node;
    }

    public function afterTraverse(array $nodes)
    {
        if (! in_array('--dry-run', $_SERVER['argv'])) {
            $file = preg_replace('#\((\$response = (\$I|\$this)->(getJson|postJson|patchJson|deleteJson)\([\s\S]*?\))\)->#ms', '$1->', $this->file->getFileContent());
            file_put_contents($this->file->getFilePath(), $file);
        }

        return parent::afterTraverse($nodes);
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Upgrade Monolog method signatures and array usage to object usage', [
            new CodeSample(
                // code before
                'public function handle(array $record) { return $record[\'context\']; }',
                // code after
                'public function handle(\Monolog\LogRecord $record) { return $record->context; }'
            ),
        ]);
    }
}
