<!--
/**
 * Place to customize the channel details display
 *
 * @author      Knut Kohl <github@knutkohl.de>
 * @copyright   2012-2014 Knut Kohl
 * @license     MIT License (MIT) http://opensource.org/licenses/MIT
 * @version     1.0.0
 */
-->

<small><img src="{ICON}" class="channel-icon tip" title="{TYPE}" alt="({TYPE})" /></small>

<strong class="tip" title="{GUID}">{NAME}</strong>

<!-- IF {DESCRIPTION} -->
    <small style="margin-left:1em">({DESCRIPTION})</small>
<!-- ENDIF -->

<!-- IF !{PUBLIC} -->
    <img src="/images/ico/lock.png" alt="[private]" style="margin-left:1em" />
<!-- ENDIF -->