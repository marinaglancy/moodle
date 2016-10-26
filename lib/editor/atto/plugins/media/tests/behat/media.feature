@editor @editor_atto @atto @atto_media @_file_upload
Feature: Add media to Atto
  To write rich text - I need to add media.

  Background:
    Given I log in as "admin"
    And I follow "Manage private files..."
    And I upload "lib/editor/atto/tests/fixtures/moodle-logo.webm" file to "Files" filemanager
    And I upload "lib/editor/atto/tests/fixtures/moodle-logo.mp4" file to "Files" filemanager
    And I upload "lib/editor/atto/tests/fixtures/moodle-logo.png" file to "Files" filemanager
    And I upload "lib/editor/atto/tests/fixtures/pretty-good-en.vtt" file to "Files" filemanager
    And I upload "lib/editor/atto/tests/fixtures/pretty-good-sv.vtt" file to "Files" filemanager
    And I click on "Save changes" "button"
    And I follow "Profile" in the user menu
    And I follow "Blog entries"
    And I follow "Add a new entry"
    And I set the field "Blog entry body" to "<p>Media test</p>"
    And I select the text in the "Blog entry body" Atto editor
    And I set the field "Entry title" to "The best video in the entire world (not really)"
    And I click on "Media" "button"

  @javascript
  Scenario: Insert some media as a link
    Given I click on "Link" "link"
    And I click on instance "1" of "Browse repositories..." "button"
      And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
      And I click on "moodle-logo.webm" "link"
    When I click on "Select this file" "button"
    Then the field "Enter name" matches value "moodle-logo.webm"
      And I wait until the page is ready
      And I click on "Insert media" "button"
    When I click on "Save changes" "button"
    Then "//a[. = 'moodle-logo.webm']" "xpath_element" should exist

  @javascript
  Scenario: Insert some media as a plain video
    Given I click on "Video" "link"
      And I click on instance "2" of "Browse repositories..." "button"
      And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
      And I click on "moodle-logo.webm" "link"
      And I click on "Select this file" "button"
      And I click on "Add video source" "link"
      And I click on instance "3" of "Browse repositories..." "button"
      And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
      And I click on "moodle-logo.mp4" "link"
      And I click on "Select this file" "button"
    When I click on "Insert media" "button"
    Then "//video[descendant::source[contains(@src, 'moodle-logo.webm')]][descendant::source[contains(@src, 'moodle-logo.mp4')]]" "xpath_element" should exist

  @javascript
  Scenario: Insert some media as a video with display settings
    Given I click on "Video" "link"
      And I click on instance "2" of "Browse repositories..." "button"
      And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
      And I click on "moodle-logo.webm" "link"
      And I click on "Select this file" "button"
      And I click on "Display options" "link"
      And I click on instance "3" of "Browse repositories..." "button"
      And I click on "Private files" "link" in instance "2" of the ".fp-repo-area" "css_element"
      And I click on "moodle-logo.png" "link"
      And I click on instance "2" of "Select this file" "button"
      And I set the field "Thumbnail width" to "420"
      And I set the field "Thumbnail height" to "69"
    When I click on "Insert media" "button"
    Then "//video[descendant::source[contains(@src, 'moodle-logo.webm')]][contains(@poster, 'moodle-logo.png')][@width=420][@height=69]" "xpath_element" should exist

  @javascript
  Scenario: Insert some media as a video with advanced settings
    Given I click on "Video" "link"
      And I click on instance "2" of "Browse repositories..." "button"
      And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
      And I click on "moodle-logo.webm" "link"
      And I click on "Select this file" "button"
      And I click on "Advanced settings" "link"
      And the field "Show controls" matches value "1"
      And I set the field "Play automatically" to "1"
      And I set the field "Muted" to "1"
      And I set the field "Loop" to "1"
    When I click on "Insert media" "button"
    Then "//video[descendant::source[contains(@src, 'moodle-logo.webm')]][@controls='true'][@loop='true'][@autoplay='true'][@autoplay='true']" "xpath_element" should exist

  @javascript
  Scenario: Insert some media as a video with tracks
    Given I click on "Video" "link"
      And I change window size to "large"
      And I click on instance "2" of "Browse repositories..." "button"
      And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
      And I click on "moodle-logo.webm" "link"
      And I click on "Select this file" "button"
      And I click on "Subtitles and captions" "link"
      And I click on instance "4" of "Browse repositories..." "button"
      And I click on "Private files" "link" in instance "2" of the ".fp-repo-area" "css_element"
      And I click on "pretty-good-sv.vtt" "link"
    When I click on instance "2" of "Select this file" "button"
    Then the field "Label" matches value "Swedish"
      And the field "Language" matches value "sv"
      And I click on "Add subtitle track" "link"
      And I click on instance "5" of "Browse repositories..." "button"
      And I click on "Private files" "link" in instance "2" of the ".fp-repo-area" "css_element"
      And I click on "pretty-good-en.vtt" "link"
    When I click on instance "2" of "Select this file" "button"
    Then instance "2" of the field "Label" matches value "English"
      And I set instance "1" of the field "Default" to "1"
      And I click on instance "1" of "Captions" "link"
      And I click on instance "6" of "Browse repositories..." "button"
      And I click on "Private files" "link" in instance "2" of the ".fp-repo-area" "css_element"
      And I click on "pretty-good-sv.vtt" "link"
    When I click on instance "2" of "Select this file" "button"
      And I click on "Overwrite" "button"
    Then instance "3" of the field "Label" matches value "Swedish"
      And I click on "Add caption track" "link"
      And I click on instance "7" of "Browse repositories..." "button"
      And I click on "Private files" "link" in instance "2" of the ".fp-repo-area" "css_element"
      And I click on "pretty-good-en.vtt" "link"
    When I click on instance "2" of "Select this file" "button"
      And I click on "Overwrite" "button"
    Then instance "4" of the field "Label" matches value "English"
      And I set instance "4" of the field "Default" to "1"
      And I click on "Descriptions" "link"
      And I click on instance "8" of "Browse repositories..." "button"
      And I click on "Private files" "link" in instance "2" of the ".fp-repo-area" "css_element"
      And  I click on "pretty-good-sv.vtt" "link"
    When I click on instance "2" of "Select this file" "button"
      And I click on "Overwrite" "button"
    Then instance "5" of the field "Label" matches value "Swedish"
      And I click on "Add description track" "link"
      And I click on instance "9" of "Browse repositories..." "button"
      And I click on "Private files" "link" in instance "2" of the ".fp-repo-area" "css_element"
      And I click on "pretty-good-en.vtt" "link"
    When I click on instance "2" of "Select this file" "button"
      And I click on "Overwrite" "button"
    Then instance "6" of the field "Label" matches value "English"
      And I set instance "5" of the field "Default" to "1"
      And I click on "Chapters" "link"
      And I click on instance "10" of "Browse repositories..." "button"
      And I click on "Private files" "link" in instance "2" of the ".fp-repo-area" "css_element"
      And  I click on "pretty-good-sv.vtt" "link"
    When I click on instance "2" of "Select this file" "button"
      And I click on "Overwrite" "button"
    Then instance "7" of the field "Label" matches value "Swedish"
      And I click on "Add chapter track" "link"
      And I click on instance "11" of "Browse repositories..." "button"
      And I click on "Private files" "link" in instance "2" of the ".fp-repo-area" "css_element"
      And I click on "pretty-good-en.vtt" "link"
    When I click on instance "2" of "Select this file" "button"
      And I click on "Overwrite" "button"
    Then instance "8" of the field "Label" matches value "English"
      And I set instance "8" of the field "Default" to "1"
      And I click on "Metadata" "link"
      And I click on instance "12" of "Browse repositories..." "button"
      And I click on "Private files" "link" in instance "2" of the ".fp-repo-area" "css_element"
      And  I click on "pretty-good-sv.vtt" "link"
    When I click on instance "2" of "Select this file" "button"
      And I click on "Overwrite" "button"
    Then instance "9" of the field "Label" matches value "Swedish"
      And I click on "Add metadata track" "link"
      And I click on instance "13" of "Browse repositories..." "button"
      And I click on "Private files" "link" in instance "2" of the ".fp-repo-area" "css_element"
      And I click on "pretty-good-en.vtt" "link"
    When I click on instance "2" of "Select this file" "button"
      And I click on "Overwrite" "button"
    Then instance "10" of the field "Label" matches value "English"
      And I set instance "9" of the field "Default" to "1"
    When I click on "Insert media" "button"
    Then "//video[descendant::source[contains(@src, 'moodle-logo.webm')]][descendant::track[contains(@src, 'pretty-good-sv.vtt')][@kind='subtitles'][@label='Swedish'][@srclang='sv'][@default='true']][descendant::track[contains(@src, 'pretty-good-en.vtt')][@kind='subtitles'][@label='English'][@srclang='en'][not(@default)]][descendant::track[contains(@src, 'pretty-good-sv.vtt')][@kind='captions'][@label='Swedish'][@srclang='sv'][not(@default)]][descendant::track[contains(@src, 'pretty-good-en.vtt')][@kind='captions'][@label='English'][@srclang='en'][@default='true']][descendant::track[contains(@src, 'pretty-good-sv.vtt')][@kind='descriptions'][@label='Swedish'][@srclang='sv'][@default='true']][descendant::track[contains(@src, 'pretty-good-en.vtt')][@kind='descriptions'][@label='English'][@srclang='en'][not(@default)]][descendant::track[contains(@src, 'pretty-good-sv.vtt')][@kind='chapters'][@label='Swedish'][@srclang='sv'][not(@default)]][descendant::track[contains(@src, 'pretty-good-en.vtt')][@kind='chapters'][@label='English'][@srclang='en'][@default='true']][descendant::track[contains(@src, 'pretty-good-sv.vtt')][@kind='metadata'][@label='Swedish'][@srclang='sv'][@default='true']][descendant::track[contains(@src, 'pretty-good-en.vtt')][@kind='metadata'][@label='English'][@srclang='en'][not(@default)]]" "xpath_element" should exist


