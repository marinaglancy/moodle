<?php

include __DIR__."/config.php";
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/testsort.php');
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading('Sortable list examples');
$dragdrop = html_writer::span($OUTPUT->pix_icon('i/dragdrop', get_string('move'), 'moodle',
    array('class' => 'iconsmall', 'title' => 'Move')), 'draghandle',
    ['tabindex' => 0, 'role' => 'button', 'aria-haspopup' => 'true', 'aria-title' => 'Move', 'data-drag-type' => 'move']);
?>

    <div class="container">
    <div class="row">
        <div class="col-sm-4">

            <!-- =========================================== Example 1 ============================================ -->
            <h2>Example 1. Without handles</h2>
            <?php
            $PAGE->requires->js_amd_inline(<<<EOT1
        require(['core/sortable_list'], function(SortableList) {
            SortableList.init('.sort-example-1');
            $('.sort-example-1 > li').on('sortablelist-drop', function(evt, info) {
                console.log('Example 1 event ' + evt.type);
                console.log(info);
            });
        })
EOT1
            );
            ?>
            <style type="text/css">
                .sortable-list-current-position { background-color: lightblue; }
            </style>

            <ul class="sort-example-1 unlist">
                <li data-drag-type="move">Apple</li>
                <li data-drag-type="move">Orange</li>
                <li data-drag-type="move">Banana <a href="#">link</a></li>
                <li data-drag-type="move">Strawberry</li>
            </ul>

            <!-- =========================================== Example 2 ============================================ -->
            <h2>Example 2. With handles</h2>
            <?php
            $PAGE->requires->js_amd_inline(<<<EOT2
        require(['core/sortable_list'], function(SortableList) {
            SortableList.init('.sort-example-2 tbody', {
                moveHandlerSelector: '.draghandle'
            });
            $('.sort-example-2 tr').on('sortablelist-drop', function(evt, info) {
                console.log('Example 2 event ' + evt.type);
                console.log(info);
            });
        })
EOT2
            );
            ?>

            <table class="sort-example-2 table-sm table-bordered">
                <thead>
                <tr><th>Header</th></tr>
                </thead>
                <tbody>
                <tr><td><?=$dragdrop?> Apple</td></tr>
                <tr><td><?=$dragdrop?> Orange</td></tr>
                <tr><td><?=$dragdrop?> Banana <a href="#">link</a></td></tr>
                <tr><td><?=$dragdrop?> Strawberry</td></tr>
                </tbody>
            </table>
        </div>

        <!-- =========================================== Example 3 ============================================ -->
        <div class="col-sm-4">
            <h2>Example 3. Several lists</h2>
            <?php
            $PAGE->requires->js_amd_inline(<<<EOT3
        require(['core/sortable_list'], function(SortableList) {
            SortableList.init('.sort-example-3[data-sort-enabled=1]');
            $('.sort-example-3 > li').on('sortablelist-drop', function(evt, info) {
                console.log('Example 3 event ' + evt.type);
                console.log(info);
            });
        })
EOT3
            );
            ?>
            <style type="text/css">
                .sort-example-3 li { padding: 3px; border: 1px solid #eee; }
                .sort-example-3.sortable-list-target {
                    border: 1px dotted black;
                    background-color: #f1f3cb;
                    min-height: 20px;
                }
            </style>

            <h3>First list</h3>
            <ul class="sort-example-3 unlist" data-sort-enabled="1">
                <li data-drag-type="move">Apple</li>
                <li data-drag-type="move">Orange</li>
                <li data-drag-type="move">Banana <a href="#">link</a></li>
                <li data-drag-type="move">Strawberry</li>
            </ul>

            <h3>Second list</h3>
            <ul class="sort-example-3 unlist" data-sort-enabled="1">
                <li data-drag-type="move">Cat</li>
                <li data-drag-type="move">Dog</li>
                <li data-drag-type="move">Fish</li>
                <li data-drag-type="move">Hippo</li>
            </ul>

            <h3>Third list</h3>
            <ul class="sort-example-3 unlist" data-sort-enabled="1">
            </ul>

        </div>

        <!-- =========================================== Example 4 ============================================ -->
        <div class="col-sm-4">
            <h2>Example 4. Drop effect</h2>
            <?php
            $PAGE->requires->js_amd_inline(<<<EOT4
        require(['core/sortable_list'], function(SortableList) {
            SortableList.init('.sort-example-4', {
                currentPositionClass: 'current-position'
            });
            $('.sort-example-4 > li').on('sortablelist-drop', function(evt, info) {
                info.element.addClass('temphighlight');
                setTimeout(function() {
                    info.element.removeClass('temphighlight');
                }, 3000);
                console.log('Example 4 event ' + evt.type);
                console.log(info);
            });
        })
EOT4
            );
            ?>
            <style type="text/css">
                .sort-example-4 li { padding: 3px; border: 1px solid #eee; }
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
                <li data-drag-type="move">Apple</li>
                <li data-drag-type="move">Orange</li>
                <li data-drag-type="move">Banana <a href="#">link</a></li>
                <li data-drag-type="move">Strawberry</li>
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
            SortableList.init('.sort-example-5', {
                isHorizontal: true,
                moveHandlerSelector: '.draghandle'
            });
            $('.sort-example-5 > li').on('sortablelist-drop', function(evt, info) {
                console.log('Example 5 event ' + evt.type);
                console.log(info);
            });
        })
EOT3
    );
    ?>
    <style type="text/css">
        .sort-example-5 li { padding: 3px; border: 1px solid #eee; }
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
        require(['core/sortable_list', 'core/str'], function(SortableList, str) {
            var elementName = function(element) {
                var name = element.attr('data-destination-name');
                return name ? name : element.text();
            };
            SortableList.init('.sort-example-6 ul', {
                moveHandlerSelector: '.draghandle',
                elementNameCallback: elementName,
                destinationNameCallback: function(parentElement, afterElement) {
                    if (!afterElement.length) {
                        if (parentElement.attr('data-is-root')) {
                            return 'To the very top'; // In real life use strings here!
                        } else {
                            return str.get_string('totopofsection', 'moodle', elementName(parentElement.parent()));
                        }
                    } else {
                        return str.get_string('movecontentafter', 'moodle', elementName(afterElement));
                    }
                }
            });
            $('.sort-example-6 ul > *').on('sortablelist-drop', function(evt, info) {
                console.log('Example 6 event ' + evt.type);
                console.log(info);
                evt.stopPropagation(); // Important for nested lists to prevent multiple targets.
            });
        })
EOT3
        );
        ?>
        <style type="text/css">
            .sort-example-6 li { padding: 3px; border: 1px solid #eee; }
        </style>

        <div class="sort-example-6">
            <ul id="l0" data-is-root="1">
                <li data-destination-name="Folder 1"><?=$dragdrop?> Folder 1
                    <ul id="l1"></ul>
                </li>
                <li data-destination-name="Folder 2"><?=$dragdrop?> Folder 2
                    <ul id="l2">
                        <li><?=$dragdrop?> Item 2-1</li>
                        <li><?=$dragdrop?> Item 2-2</li>
                        <li><?=$dragdrop?> Item 2-3</li>
                    </ul>
                </li>
                <li data-destination-name="Folder 3"><?=$dragdrop?> Folder 3
                    <ul id="l3">
                        <li><?=$dragdrop?> Item 3-1</li>
                        <li><?=$dragdrop?> Item 3-2</li>
                        <li><?=$dragdrop?> Item 3-3</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>


    <div>
        <!-- =========================================== Example 7 Embedded lists (examples of callbacks) ============================================ -->
        <h2>Example 7. Embedded lists (examples of callbacks) </h2>
        <?php
        $dragdrop1 = html_writer::span($OUTPUT->pix_icon('i/dragdrop', get_string('move'), 'moodle',
            array('class' => 'iconsmall', 'title' => 'Move')), 'draghandle-section',
            ['tabindex' => 0, 'role' => 'button', 'aria-haspopup' => 'true', 'aria-title' => 'Move', 'data-drag-type' => 'move']);
        $dragdrop2 = html_writer::span($OUTPUT->pix_icon('i/dragdrop', get_string('move'), 'moodle',
            array('class' => 'iconsmall', 'title' => 'Move')), 'draghandle-activity',
            ['tabindex' => 0, 'role' => 'button', 'aria-haspopup' => 'true', 'aria-title' => 'Move', 'data-drag-type' => 'move']);
        $PAGE->requires->js_amd_inline(<<<EOT3
        require(['core/sortable_list', 'core/str'], function(SortableList, str) {
            var sectionName = function(element) {
                return element.attr('data-sectionname');
            };
            
            // Sort sections.
            SortableList.init('.sort-example-7a', {
                moveHandlerSelector: '.draghandle-section',
                elementNameCallback: sectionName
            });
            $('.sort-example-7a > *').on('sortablelist-drop sortablelist-dragstart sortablelist-drag sortablelist-dragend', function(evt, info) {
                console.log('Example 7 section event ' + evt.type);
                console.log(info);
                evt.stopPropagation(); // Important for nested lists to prevent multiple targets.
            });
            
            // Sort activities.
            SortableList.init('.sort-example-7b', {
                moveHandlerSelector: '.draghandle-activity',
                destinationNameCallback: function(parentElement, afterElement) {
                    if (!afterElement.length) {
                        return str.get_string('totopofsection', 'moodle', sectionName(parentElement.parent()));
                    } else {
                        return str.get_string('afterresource', 'moodle', afterElement.text());
                    }
                }
            });
            $('.sort-example-7b > *').on('sortablelist-drop sortablelist-dragstart sortablelist-drag sortablelist-dragend', function(evt, info) {
                console.log('Example 7 activity event ' + evt.type);
                console.log(info);
                evt.stopPropagation(); // Important for nested lists to prevent multiple targets.
            });
        })
EOT3
        );
        ?>
        <style type="text/css">
            .sort-example-7b {
                min-height: 20px;
                width: 100%;
            }
            .sort-example-7b.sortable-list-target {
                border: 1px dotted black;
                background-color: #f1f3cb;
            }
        </style>

        <div>
            <ul class="sort-example-7a">
                <li data-sectionname="Section A">
                    <h3><?=$dragdrop1?> Section A</h3>
                    <ul class="sort-example-7b">

                    </ul>
                </li>
                <li data-sectionname="Section B">
                    <h3><?=$dragdrop1?> Section B</h3>
                    <ul class="sort-example-7b">
                        <li><?=$dragdrop2?> Item B-1</li>
                        <li><?=$dragdrop2?> Item B-2</li>
                        <li><?=$dragdrop2?> Item B-3</li>
                    </ul>
                </li>
                <li data-sectionname="Section C">
                    <h3><?=$dragdrop1?> Section C</h3>
                    <ul class="sort-example-7b">
                        <li><?=$dragdrop2?> Item C-1</li>
                        <li><?=$dragdrop2?> Item C-2</li>
                        <li><?=$dragdrop2?> Item C-3</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <!-- =========================================== Example 8 vertical and horizontal ============================================ -->
    <h2>Example 8. Drag elements from vertical list into horizontal list</h2>
<?php
$moveback = html_writer::span($OUTPUT->pix_icon('i/delete', get_string('delete'), 'moodle',
    array('class' => 'iconsmall', 'title' => 'Remove')), 'moveback',
    ['tabindex' => 0, 'role' => 'button', 'aria-title' => 'Delete']);
$moveto = html_writer::span($OUTPUT->pix_icon('t/add', get_string('add'), 'moodle',
    array('class' => 'iconsmall', 'title' => 'Add')), 'moveto',
    ['tabindex' => 0, 'role' => 'button', 'aria-title' => 'Add']);

$PAGE->requires->js_amd_inline(<<<EOT3
        require(['jquery', 'core/sortable_list', 'core/str'], function($, SortableList, str) {
            SortableList.init('.sort-example-8', {
                targetListSelector: '.sort-example-8#selectto',
                moveHandlerSelector: '.draghandle',
                isHorizontal: true
            });
            $('#selectto').on('click', '.moveback', function(evt) {
                if (evt.which === 1) {
                    $('#selectfrom').append($(evt.currentTarget).closest('li').detach());
                }
            });
            $('#selectfrom').on('click', '.moveto', function(evt) {
                if (evt.which === 1) {
                    $('#selectto').append($(evt.currentTarget).closest('li').detach());
                }
            });
            $('.sort-example-8 > li').on('sortablelist-drop', function(evt, info) {
                console.log('Example 8 event ' + evt.type);
                console.log(info);
            });
        })
EOT3
);
?>
    <style type="text/css">
        .sort-example-8 li { padding: 3px; border: 1px solid #eee; min-width: 50px; }
        .sort-example-8 { background: #d6f8cd; width: 100%; min-height: 30px; }
        .moveback, .moveto { cursor: pointer; }
        #selectfrom .moveback { display: none; }
        #selectto .moveto { display: none; }
    </style>

    <div class="container">
        <div class="row">
            <div class="col-sm-3">
                <ul class="sort-example-8 unlist" id="selectfrom">
                    <li><?=$dragdrop?> Apple <?=$moveback?> <?=$moveto?></li>
                    <li><?=$dragdrop?> Orange <?=$moveback?> <?=$moveto?></li>
                    <li><?=$dragdrop?> Banana <a href="#">link</a> <?=$moveback?> <?=$moveto?></li>
                    <li><?=$dragdrop?> Strawberry <?=$moveback?> <?=$moveto?></li>
                    <li><?=$dragdrop?> Blueberry <?=$moveback?> <?=$moveto?></li>
                    <li><?=$dragdrop?> Peach <?=$moveback?> <?=$moveto?></li>
                    <li><?=$dragdrop?> Lemon <?=$moveback?> <?=$moveto?></li>

                </ul>
            </div>
            <div class="col-sm-9">
                <ul class="sort-example-8 inline-list" id="selectto">

                </ul>
            </div>
        </div>
    </div>

<?php
echo $OUTPUT->footer();
