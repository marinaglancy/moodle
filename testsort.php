<?php

include __DIR__."/config.php";
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/testsort.php');
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading('Sortable list examples');
$dragdrop = $OUTPUT->pix_icon('i/dragdrop', get_string('move'), 'moodle', array('class' => 'iconsmall draghandle', 'title' => ''));
?>

    <div class="container">
    <div class="row">
        <div class="col-sm-4">

            <!-- =========================================== Example 1 ============================================ -->
            <h2>Example 1. Without handles</h2>
            <?php
            $PAGE->requires->js_amd_inline(<<<EOT1
        require(['core/sortable_list'], function(SortableList) {
            SortableList.init({
                listSelector: '.sort-example-1',
                onDrop: function(info) { console.log(info); }
            });
        })
EOT1
            );
            ?>
            <style type="text/css">
                .sort-example-1 li[draggable] { cursor: move; }
                .sortable-list-current-position { background-color: lightblue; }
            </style>

            <ul class="sort-example-1 unlist">
                <li draggable="true">Apple</li>
                <li draggable="true">Orange</li>
                <li draggable="true">Banana <a href="#">link</a></li>
                <li draggable="true">Strawberry</li>
            </ul>

            <!-- =========================================== Example 2 ============================================ -->
            <h2>Example 2. With handles</h2>
            <?php
            $PAGE->requires->js_amd_inline(<<<EOT2
        require(['core/sortable_list'], function(SortableList) {
            SortableList.init({
                listSelector: '.sort-example-2 tbody',
                moveHandlerSelector: '.draghandle',
                onDrop: function(info) { console.log(info); }
            });
        })
EOT2
            );
            ?>
            <style type="text/css">
                .sort-example-2 tr[draggable] .draghandle { cursor: move; }
            </style>

            <table class="sort-example-2 table-sm table-bordered">
                <thead>
                <tr><th>Header</th></tr>
                </thead>
                <tbody>
                <tr draggable="true"><td><?=$dragdrop?> Apple</td></tr>
                <tr draggable="true"><td><?=$dragdrop?> Orange</td></tr>
                <tr draggable="true"><td><?=$dragdrop?> Banana <a href="#">link</a></td></tr>
                <tr draggable="true"><td><?=$dragdrop?> Strawberry</td></tr>
                </tbody>
            </table>
        </div>

        <!-- =========================================== Example 3 ============================================ -->
        <div class="col-sm-4">
            <h2>Example 3. Several lists</h2>
            <?php
            $PAGE->requires->js_amd_inline(<<<EOT3
        require(['core/sortable_list'], function(SortableList) {
            SortableList.init({
                listSelector: '.sort-example-3',
                onDrop: function(info) { console.log(info); }
            });
        })
EOT3
            );
            ?>
            <style type="text/css">
                .sort-example-3 li { cursor: move; padding: 3px; border: 1px solid #eee; }
                .sort-example-3.sortable-list-target {
                    border: 1px dotted black;
                    background-color: #f1f3cb;
                    min-height: 20px;
                }
            </style>

            <h3>First list</h3>
            <ul class="sort-example-3 unlist">
                <li draggable="true">Apple</li>
                <li draggable="true">Orange</li>
                <li draggable="true">Banana <a href="#">link</a></li>
                <li draggable="true">Strawberry</li>
            </ul>

            <h3>Second list</h3>
            <ul class="sort-example-3 unlist">
                <li draggable="true">Cat</li>
                <li draggable="true">Dog</li>
                <li draggable="true">Fish</li>
                <li draggable="true">Hippo</li>
            </ul>

            <h3>Third list</h3>
            <ul class="sort-example-3 unlist">
            </ul>

        </div>

        <!-- =========================================== Example 4 ============================================ -->
        <div class="col-sm-4">
            <h2>Example 4. Drop effect</h2>
            <?php
            $PAGE->requires->js_amd_inline(<<<EOT4
        require(['core/sortable_list'], function(SortableList) {
            SortableList.init({
                listSelector: '.sort-example-4',
                currentPositionClass: 'current-position',
                onDrop: function(info) { 
                    info.draggedElement.addClass('temphighlight');
                    setTimeout(function() {
                        info.draggedElement.removeClass('temphighlight');
                    }, 3000);
                }
            });
        })
EOT4
            );
            ?>
            <style type="text/css">
                .sort-example-4 li { cursor: move; padding: 3px; border: 1px solid #eee; }
                .sort-example-4 li.current-position { opacity: 0.5; }
                .temphighlight {
                    -webkit-animation: target-fade 1s;
                    -moz-animation: target-fade 1s;
                    -o-animation: target-fade 1s;
                    animation: target-fade 1s;
                }

                @-webkit-keyframes target-fade,
                @-moz-keyframes target-fade,
                @-o-keyframes target-fade,
                @keyframes target-fade {
                    from { background-color: #EBF09E; } /* [1] */
                    to { background-color: transparent; }
                }

                @-moz-keyframes target-fade {
                    from { background-color: #EBF09E; } /* [1] */
                    to { background-color: transparent; }
                }

                @-o-keyframes target-fade {
                    from { background-color: #EBF09E; } /* [1] */
                    to { background-color: transparent; }
                }

                @keyframes target-fade {
                    from { background-color: #EBF09E; } /* [1] */
                    to { background-color: transparent; }
                }

            </style>

            <ul class="sort-example-4 unlist">
                <li draggable="true">Apple</li>
                <li draggable="true">Orange</li>
                <li draggable="true">Banana <a href="#">link</a></li>
                <li draggable="true">Strawberry</li>
            </ul>
        </div>
    </div>
    </div>

<div>
    <!-- =========================================== Example 5 horizontal ============================================ -->
        <h2>Example 5. Horizontal list</h2>
    <?php
    $PAGE->requires->js_amd_inline(<<<EOT3
        require(['core/sortable_list'], function(SortableList) {
            SortableList.init({
                listSelector: '.sort-example-5',
                isHorizontal: true,
                moveHandlerSelector: '.draghandle',
                onDrop: function(info) { console.log(info); }
            });
        })
EOT3
    );
    ?>
    <style type="text/css">
        .sort-example-5 li { padding: 3px; border: 1px solid #eee; }
        .sort-example-5 .draghandle { cursor: move; }
    </style>

    <ul class="list-inline sort-example-5">
        <li class="list-inline-item"><?=$dragdrop?> Lorem ipsum<br>line 2<br>line 3</li>
        <li class="list-inline-item"><?=$dragdrop?> Phasellus iaculis<br>line 2<br>line 3</li>
        <li class="list-inline-item"><?=$dragdrop?> Nulla volutpat<br>line 2<br>line 3</li>
    </ul>
</div>

    <div>
        <!-- =========================================== Example 6 Hierarchy ============================================ -->
        <h2>Example 6. Hirarchy</h2>
        <?php
        $PAGE->requires->js_amd_inline(<<<EOT3
        require(['core/sortable_list'], function(SortableList) {
            SortableList.init({
                listSelector: '.sort-example-6 ul',
                moveHandlerSelector: '.draghandle',
                onDrop: function(info) { console.log(info); }
            });
        })
EOT3
        );
        ?>
        <style type="text/css">
            .sort-example-6 li { padding: 3px; border: 1px solid #eee; }
            .sort-example-6 .draghandle { cursor: move; }
            .sort-example-6 .sortable-list-over-element > ul {
                min-height: 40px;
                width: 100%;
                background-color: #EBF09E;
            }
        </style>

        <div class="sort-example-6">
            <ul id="l0">
                <li id="item1"><?=$dragdrop?> Item 1
                    <ul id="item1sublist"></ul>
                </li>
                <li><?=$dragdrop?> Item 2
                    <ul id="l2">
                        <li><?=$dragdrop?> Item 2-1</li>
                        <li><?=$dragdrop?> Item 2-2</li>
                        <li><?=$dragdrop?> Item 2-3</li>
                    </ul>
                </li>
                <li><?=$dragdrop?> Item 3
                    <ul id="l3">
                        <li><?=$dragdrop?> Item 3-1</li>
                        <li><?=$dragdrop?> Item 3-2</li>
                        <li><?=$dragdrop?> Item 3-3</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

<?php
echo $OUTPUT->footer();
