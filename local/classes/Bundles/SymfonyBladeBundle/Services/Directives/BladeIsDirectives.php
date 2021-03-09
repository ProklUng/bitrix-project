<?php

namespace Local\Bundles\SymfonyBladeBundle\Services\Directives;

use Illuminate\View\Compilers\BladeCompiler;
use Local\Bundles\SymfonyBladeBundle\Services\Directives\Utils\Parser;

/**
 * Class BladeIsDirectives
 * Logical directives.
 * @package Local\Bundles\SymfonyBladeBundle\Services\Directives
 *
 * @since 09.03.2021
 */
class BladeIsDirectives implements BladeDirectiveInterface
{
    /**
     * @inheritDoc
     */
    public function handlers(BladeCompiler $compiler) : array
    {
        return [
            'istrue' => function ($expression) {
                if (strpos($expression, ',') !== false) {
                    $expression = Parser::multipleArgs($expression);

                    return implode('', [
                        "<?php if (isset({$expression->get(0)}) && (bool) {$expression->get(0)} === true) : ?>",
                        "<?php echo {$expression->get(1)}; ?>",
                        '<?php endif; ?>',
                    ]);
                }

                return "<?php if (isset({$expression}) && (bool) {$expression} === true) : ?>";
            },

            'endistrue' => function ($expression) {
                return '<?php endif; ?>';
            },

            'isfalse' => function ($expression) {
                if (strpos($expression, ',') !== false) {
                    $expression = Parser::multipleArgs($expression);

                    return implode('', [
                        "<?php if (isset({$expression->get(0)}) && (bool) {$expression->get(0)} === false) : ?>",
                        "<?php echo {$expression->get(1)}; ?>",
                        '<?php endif; ?>',
                    ]);
                }

                return "<?php if (isset({$expression}) && (bool) {$expression} === false) : ?>";
            },

            'endisfalse' => function ($expression) {
                return '<?php endif; ?>';
            },
        ];
    }
}
