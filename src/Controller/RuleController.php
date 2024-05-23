<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class RuleController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }

    #[Route('/process-rules', methods: ['POST'])]
    public function processRules(Request $request, CacheItemPoolInterface $pool): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['rules'])) {
            return new Response('Invalid rules payload.', Response::HTTP_BAD_REQUEST);
        }

        $expressionLanguage = new ExpressionLanguage($pool);

        // Register custom functions
        $this->registerCustomFunctions($expressionLanguage);

        // Convert rules to Symfony ExpressionLanguage syntax
        $expression = $this->convertToExpression($data);

        // Example context data
        $context = ['name' => 'John Doe', 'age' => 30, 'birthday' => '1990-01-01'];

        // Evaluate the expression
        $result = $expressionLanguage->evaluate($expression, $context);

        return new JsonResponse(['result' => $result, 'expression' => $expression, 'context' => $context]);
    }

    private function convertToExpression(array $group): string
    {
        return $this->parseGroup($group);
    }

    private function parseGroup(array $group): string
    {
        // Map logical operators from QueryBuilder to ExpressionLanguage
        $logicalOperatorMapping = [
            'AND' => '&&',
            'OR' => '||'
        ];

        $logicalOperator = $logicalOperatorMapping[strtoupper($group['condition'])];
        $expressions = [];

        foreach ($group['rules'] as $rule) {
            if (isset($rule['rules'])) {
                // Nested group
                $expressions[] = '(' . $this->parseGroup($rule) . ')';
            } else {
                // Single rule
                $expressions[] = $this->parseRule($rule);
            }
        }

        return implode(" $logicalOperator ", $expressions);
    }

    private function parseRule(array $rule): string
    {
        $field = $rule['field'];
        $operator = $rule['operator'];
        $value = $rule['value'];

        // Map QueryBuilder operators to ExpressionLanguage operators
        $operatorMapping = [
            'equal' => '==',
            'not_equal' => '!=',
            'in' => 'in',
            'not_in' => 'not in',
            'less' => '<',
            'less_or_equal' => '<=',
            'greater' => '>',
            'greater_or_equal' => '>=',
            'begins_with' => 'str_starts_with',
            'not_begins_with' => '!str_starts_with',
            'contains' => 'str_contains',
            'not_contains' => '!str_contains',
            'ends_with' => 'str_ends_with',
            'not_ends_with' => '!str_ends_with',
            'is_empty' => "is_empty",
            'is_not_empty' => "is_not_empty",
            'is_null' => 'is_null',
            'is_not_null' => 'is_not_null',
        ];

        if (isset($operatorMapping[$operator])) {
            $operator = $operatorMapping[$operator];
        }

        // Handle different operators
        switch ($operator) {
            case 'in':
                return sprintf('%s in [%s]', $field, implode(', ', $value));
            case 'not in':
                return sprintf('%s not in [%s]', $field, implode(', ', $value));
            case 'str_starts_with':
                return sprintf('str_starts_with(%s, "%s")', $field, $value);
            case '!str_starts_with':
                return sprintf('!str_starts_with(%s, "%s")', $field, $value);
            case 'str_contains':
                return sprintf('str_contains(%s, "%s")', $field, $value);
            case '!str_contains':
                return sprintf('!str_contains(%s, "%s")', $field, $value);
            case 'str_ends_with':
                return sprintf('str_ends_with(%s, "%s")', $field, $value);
            case '!str_ends_with':
                return sprintf('!str_ends_with(%s, "%s")', $field, $value);
            case 'is_null':
                return sprintf('%s === null', $field);
            case 'is_not_null':
                return sprintf('%s !== null', $field);
            case 'is_empty':
                return sprintf('empty(%s)', $field);
            case 'is_not_empty':
                return sprintf('!empty(%s)', $field);
            default:
                return sprintf('%s %s "%s"', $field, $operator, $value);
        }
    }

    private function registerCustomFunctions(ExpressionLanguage $expressionLanguage)
    {
        // Register custom function for str_starts_with
        $expressionLanguage->register('str_starts_with', function ($str, $prefix) {
            return sprintf('str_starts_with(%s, %s)', $str, $prefix); // Compiler function
        }, function (array $variables, $str, $prefix) {
            return str_starts_with($str, $prefix); // Evaluator function
        });

        // Register custom function for str_contains
        $expressionLanguage->register('str_contains', function ($str, $substr) {
            return sprintf('str_contains(%s, %s)', $str, $substr); // Compiler function
        }, function (array $variables, $str, $substr) {
            return str_contains($str, $substr); // Evaluator function
        });

        // Register custom function for str_ends_with
        $expressionLanguage->register('str_ends_with', function ($str, $suffix) {
            return sprintf('str_ends_with(%s, %s)', $str, $suffix); // Compiler function
        }, function (array $variables, $str, $suffix) {
            return str_ends_with($str, $suffix); // Evaluator function
        });

        // Register custom function for str_ends_with
        $expressionLanguage->register('empty', function ($str) {
            return sprintf('empty(%s)', $str); // Compiler function
        }, function (array $variables, $str) {
            return empty($str); // Evaluator function
        });
    }
}
