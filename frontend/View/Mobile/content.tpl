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

<!-- -------------------------------------------------------------------------
PAGE 1
-------------------------------------------------------------------------- -->
<div data-role="page" id="page-home" data-theme="a" data-view="{VIEW1ST}">

    <!-- Header -->
    <div data-role="header" data-id="header">
        <a id="btn-home" class="ui-btn-left ui-btn ui-btn-icon-notext ui-btn-corner-all"
           data-iconpos="notext" data-role="button" data-icon="home" title=" Home ">
            <span class="ui-btn-inner ui-btn-corner-all">
                <span class="ui-btn-text"> Home </span>
                <span data-form="ui-icon" class="ui-icon ui-icon-home ui-icon-shadow"></span>
            </span>
        </a>
        <h1 id="view"></h1>
        <a id="btn-refresh" class="ui-btn-right ui-btn ui-btn-icon-notext ui-btn-corner-all"
           data-iconpos="notext" data-role="button" data-icon="refresh" title=" {{Refresh}} ">
            <span class="ui-btn-inner ui-btn-corner-all">
                <span class="ui-btn-text"> Navigation </span>
                <span data-form="ui-icon" class="ui-icon ui-icon-refresh ui-icon-shadow"></span>
            </span>
        </a>
    </div>

    <!-- Content -->
    <div data-role="content">
        <div id="chart"></div>

        <table id="table-cons" data-role="table" class="ui-responsive">
            <thead>
            <tr>
                <th>{{Channel}}</th>
                <th>{{Production}} / {{Consumption}}</th>
                <th>{{Cost}}</th>
            </tr>
            </thead>
            <tbody />
        </table>

        <a href="#page-select" data-role="button" data-icon="arrow-r" data-iconpos="right">
            {{SelectView}}
        </a>
    </div>

    <!-- Footer -->
    <div data-role="footer" data-id="footer">
        <h1>{strip_tags:TITLE}</h1>
    </div>

</div>

<!-- -------------------------------------------------------------------------
PAGE 2
-------------------------------------------------------------------------- -->
<div data-role="page" id="page-select" data-theme="a">

    <!-- Header -->
    <div data-role="header" data-id="header" data-position="fixed">
        <a href="#page-home" class="ui-btn-left ui-btn ui-btn-icon-notext ui-btn-corner-all"
           data-iconpos="notext" data-role="button" data-icon="home" title=" Home ">
            <span class="ui-btn-inner ui-btn-corner-all">
                <span class="ui-btn-text"> Home </span>
                <span data-form="ui-icon" class="ui-icon ui-icon-home ui-icon-shadow"></span>
            </span>
        </a>
        <h1>{{Selection}}</h1>
    </div>

    <!-- Content -->
    <div data-role="content" class="ui-title">
        <div data-role="controlgroup">
            <!-- BEGIN VIEWS -->
            <a href="#page-home" data-role="button" data-icon="arrow-l"
               onclick="$('#page-home').data('view', '{NAME}')">
                {NAME}
            </a>
            <!-- END -->
        </div>
    </div>

    <!-- Footer -->
    <div data-role="footer" data-id="footer" data-position="fixed">
        <h1>{strip_tags:TITLE}</h1>
    </div>


</div>
