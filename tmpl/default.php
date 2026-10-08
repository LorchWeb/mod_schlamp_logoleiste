<?php
/** @license GNU General Public License version 2 or later */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

if ($logos === []) {
    return;
}

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$number = static fn (string $key, int $default, int $min, int $max): int => max($min, min($max, (int) $params->get($key, $default)));
$desktop = min(count($logos), $number('visible_desktop', 6, 1, 12));
$tablet = min($desktop, $number('visible_tablet', 3, 1, 12));
$mobile = min($tablet, $number('visible_mobile', 2, 1, 12));
$style = '--sl-desktop:' . $desktop . ';--sl-tablet:' . $tablet . ';--sl-mobile:' . $mobile
    . ';--sl-duration:' . $number('duration', 30, 5, 300) . 's;--sl-gap:' . $number('gap', 40, 0, 120)
    . 'px;--sl-height:' . $number('height', 120, 30, 300) . 'px;--sl-padding:' . $number('padding', 0, 0, 150) . 'px';
$width = $number('width', 0, 0, 1200);
$background = trim((string) $params->get('background_color', '#ffffff'));
$background = (bool) $params->get('background_enabled', 0) && preg_match('/^#[0-9a-fA-F]{6}$/D', $background) ? $background : 'transparent';
$style .= ';--sl-width:' . $width . 'px;--sl-background:' . $background;
$newWindow = (bool) $params->get('new_window', 0);
$label = trim((string) $params->get('label', '')) ?: Text::_('MOD_SCHLAMP_LOGOLEISTE_REGION');
?>
<section class="schlamp-logos<?= $width > 0 ? ' has-fixed-width' : '' ?>" data-schlamp-logos style="<?= $escape($style) ?>" aria-label="<?= $escape($label) ?>">
    <div class="schlamp-logos__viewport">
        <div class="schlamp-logos__track">
            <div class="schlamp-logos__group" role="list">
                <?php foreach ($logos as $logo) : ?>
                    <div class="schlamp-logos__item" role="listitem">
                        <?php if ($logo['url'] !== '') : ?>
                            <a class="schlamp-logos__link" href="<?= $escape($logo['url']) ?>"<?= $newWindow ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
                        <?php endif; ?>
                        <img src="<?= $escape($logo['src']) ?>" alt="<?= $escape($logo['alt']) ?>" decoding="async" draggable="false">
                        <?php if ($logo['url'] !== '') : ?>
                                <?php if ($newWindow) : ?><span class="schlamp-logos__sr-only"><?= $escape(Text::_('MOD_SCHLAMP_LOGOLEISTE_NEW_WINDOW')) ?></span><?php endif; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
