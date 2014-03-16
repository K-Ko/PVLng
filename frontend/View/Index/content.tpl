<!--
/**
 *
 *
 * @author      Knut Kohl <github@knutkohl.de>
 * @copyright   2012-2013 Knut Kohl
 * @license     GNU General Public License http://www.gnu.org/licenses/gpl.txt
 * @version     1.0.0
 */
-->

<input type="hidden" id="fromdate" />
<input type="hidden" id="todate" />

<div id="nav" class="grid_10" style="margin-top:1em">

    <div class="alpha grid_4">
        <table>
        <tr>
            <td>
                <span class="ui-icon ui-icon-triangle-1-w tip"
                      title="{{PrevDay}} (Alt+P)" onclick="changeDates(-1)"></span>
            </td>
            <td>
                <input class="c" type="text" id="from" size="10" />
            </td>
            <td style="padding:0 .5em;font-weight:bold">
                &mdash;
            </td>
            <td>
                <input class="c" type="text" id="to" size="10" />
            </td>
            <td>
                <span class="ui-icon ui-icon-triangle-1-e tip"
                      title="{{NextDay}} (Alt+N)" onclick="changeDates(1)"></span>
            </td>
            <td style="padding-left:.5em">
                <button id="btn-reset" class="tip" title="{{ChartTodayHint}}">{{Today}}</button>
            </td>
        </tr>
        </table>
    </div>
    <div class="grid_2 c" style="margin:0 2%">
        &nbsp;
        <img id="loading" src="/images/loading.gif" style="width:48px;height:47px" width="48" height="47" alt="loading ..." />
        <img id="modified" src="/images/modified.png" width="24" height="24"
             style="display:none;margin-top:6px;width:24px;height:24px" alt="[ unsaved changes ]"
             class="tip" title="{{UnsavedChanges}}" />
        &nbsp;
    </div>
    <div class="grid_4 omega" style="text-align:right">
        <img src="/images/ico/arrow-switch.png" style="margin-right:.5em"
             onclick="$('.p-select').toggle();" class="tip" tip="{{UseOwnConsolidation}}" />
        <span class="p-select">{PRESETSELECT}</span>
        <span class="p-select" style="display:none">
            <input class="numbersOnly r" style="margin-right:.5em" type="text"
                   id="periodcnt" value="1" size="2" />
            {PERIODSELECT}
        </span>
        <span style="margin-left:.5em">
            <button id="btn-refresh" class="tip" title="{{ChartRefreshHint}}">{{Refresh}}</button>
        </span>
    </div>
</div>

<div class="clear"></div>

<div id="chart" class="grid_10 c">
    <div id="top-select" style="display:none">
        <p class="b">{{NoChannelsSelectedYet}}</p>
        <label for="top-load-view" class="b" style="margin-right:1em">{{Variants}}:</label>
        <select id="top-load-view"></select>
    </div>
</div>

<div class="clear"></div>

<!-- IF {USER} -->
    <!-- INCLUDE content.private.inc.tpl -->
<!-- ELSE -->
    <!-- INCLUDE content.public.inc.tpl -->
<!-- ENDIF -->

<div id="dialog-reading" title="{{DeleteReading}}">
    <p>
        <span class="ui-icon ui-icon-alert" style="float:left;margin:0 7px 20px 0"></span>
        {{DeleteReadingConfirm}}
    </p>
    <ul>
        <li id="reading-serie"></li>
        <li id="reading-timestamp"></li>
        <li>{{Reading}} : <span id="reading-value"></span></li>
    </ul>
</div>

<!-- INCLUDE dialog.chart.tpl -->
