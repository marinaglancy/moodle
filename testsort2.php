<?php

include __DIR__."/config.php";
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/testsort2.php');
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading('Sortable list examples');
$dragdrop = $OUTPUT->render_from_template('core/drag_handle', ['movetitle' => get_string('move')]);
?>
            <!-- =========================================== Example 9 ============================================ -->
            <h2>Example 9. Resorting columns</h2>
            <?php
            $PAGE->requires->js_amd_inline(<<<EOT2
        require(['jquery', 'core/sortable_list'], function($, SortableList) {
            new SortableList('.sort-example-9 thead tr', {isHorizontal: true});
            
            var addColorder = function() {
                // Add "data-colorder" attribute to each cell in the row (starting with 1).
                var colorder = 1;
                $(this).children().each(function() {
                    $(this).attr('data-colorder', colorder++) ;                
                });
            };
            
            var moveCell = function(tr, idx, beforeidx) {
                var cell = $(tr).children('[data-colorder=' + idx + ']')[0];
                if (beforeidx) {
                    var beforeCell = $(tr).children('[data-colorder=' + beforeidx + ']')[0];
                    tr.insertBefore(cell, beforeCell);
                } else {
                    tr.appendChild(cell);
                }                        
            };
            
            $('.sort-example-9 thead th')
            .on(SortableList.EVENTS.DRAGSTART, function(evt, info) {
                // Add "column order" attribute to each cell in each row for easier reference.
                $('.sort-example-9').find('tr').each(addColorder);
            })
            .on(SortableList.EVENTS.DRAG, function(evt, info) {
                // Each time user changes position of a header cell do the same change in every other row.
                var idx = info.element.attr('data-colorder'),
                    beforeidx = info.targetNextElement.attr('data-colorder');
                $('.sort-example-9 tbody tr').each(function() {
                    moveCell(this, idx, beforeidx);
                });
            })
            .on(SortableList.EVENTS.DRAGEND, function(evt, info) {
                // Remove colorders.
                $('.sort-example-9 tr > *').attr('data-colorder', null);
            })
            .on(SortableList.EVENTS.DROP, function(evt, info) {
                // Drag and drop finished, do custom stuff.
                if (info.positionChanged) {
                    console.log('Example 9 event ' + evt.type);
                    console.log(info);
                }
            });
        })
EOT2
            );
            ?>

            <table class="sort-example-9 table-sm table-bordered">
                <thead>
                <tr><th><?=$dragdrop?> Fruits</th><th><?=$dragdrop?> Vegetables</th><th><?=$dragdrop?> Berries</th></tr>
                </thead>
                <tbody>
                <tr><td>F: Apple</td><td>V: Cucumber</td><td>B: Strawberry</td></tr>
                <tr><td>F: Orange</td><td>V: Potato</td><td>B: Blueberry</td></tr>
                <tr><td>F: Banana <a href="#">link</a></td><td>V: Carrot</td><td>B: Watermelon</td></tr>
                </tbody>
            </table>
        </div>

<?php
echo $OUTPUT->footer();
