@mod @mod_quiz @mod_quiz_stress
Feature: Add quiz and attempt it as 100 students
  In order to evaluate students
  As a teacher
  I need to create a quiz

  @javascript
  Scenario: Add a quiz
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Terry1 | Teacher1 | teacher1@moodle.com |
      | student001 | Sam001 | Student001 | student001@moodle.com |
      | student002 | Sam002 | Student002 | student002@moodle.com |
      | student003 | Sam003 | Student003 | student003@moodle.com |
      | student004 | Sam004 | Student004 | student004@moodle.com |
      | student005 | Sam005 | Student005 | student005@moodle.com |
      | student006 | Sam006 | Student006 | student006@moodle.com |
      | student007 | Sam007 | Student007 | student007@moodle.com |
      | student008 | Sam008 | Student008 | student008@moodle.com |
      | student009 | Sam009 | Student009 | student009@moodle.com |
      | student010 | Sam010 | Student010 | student010@moodle.com |
      | student011 | Sam011 | Student011 | student011@moodle.com |
      | student012 | Sam012 | Student012 | student012@moodle.com |
      | student013 | Sam013 | Student013 | student013@moodle.com |
      | student014 | Sam014 | Student014 | student014@moodle.com |
      | student015 | Sam015 | Student015 | student015@moodle.com |
      | student016 | Sam016 | Student016 | student016@moodle.com |
      | student017 | Sam017 | Student017 | student017@moodle.com |
      | student018 | Sam018 | Student018 | student018@moodle.com |
      | student019 | Sam019 | Student019 | student019@moodle.com |
      | student020 | Sam020 | Student020 | student020@moodle.com |
      | student021 | Sam021 | Student021 | student021@moodle.com |
      | student022 | Sam022 | Student022 | student022@moodle.com |
      | student023 | Sam023 | Student023 | student023@moodle.com |
      | student024 | Sam024 | Student024 | student024@moodle.com |
      | student025 | Sam025 | Student025 | student025@moodle.com |
      | student026 | Sam026 | Student026 | student026@moodle.com |
      | student027 | Sam027 | Student027 | student027@moodle.com |
      | student028 | Sam028 | Student028 | student028@moodle.com |
      | student029 | Sam029 | Student029 | student029@moodle.com |
      | student030 | Sam030 | Student030 | student030@moodle.com |
      | student031 | Sam031 | Student031 | student031@moodle.com |
      | student032 | Sam032 | Student032 | student032@moodle.com |
      | student033 | Sam033 | Student033 | student033@moodle.com |
      | student034 | Sam034 | Student034 | student034@moodle.com |
      | student035 | Sam035 | Student035 | student035@moodle.com |
      | student036 | Sam036 | Student036 | student036@moodle.com |
      | student037 | Sam037 | Student037 | student037@moodle.com |
      | student038 | Sam038 | Student038 | student038@moodle.com |
      | student039 | Sam039 | Student039 | student039@moodle.com |
      | student040 | Sam040 | Student040 | student040@moodle.com |
      | student041 | Sam041 | Student041 | student041@moodle.com |
      | student042 | Sam042 | Student042 | student042@moodle.com |
      | student043 | Sam043 | Student043 | student043@moodle.com |
      | student044 | Sam044 | Student044 | student044@moodle.com |
      | student045 | Sam045 | Student045 | student045@moodle.com |
      | student046 | Sam046 | Student046 | student046@moodle.com |
      | student047 | Sam047 | Student047 | student047@moodle.com |
      | student048 | Sam048 | Student048 | student048@moodle.com |
      | student049 | Sam049 | Student049 | student049@moodle.com |
      | student050 | Sam050 | Student050 | student050@moodle.com |
      | student051 | Sam051 | Student051 | student051@moodle.com |
      | student052 | Sam052 | Student052 | student052@moodle.com |
      | student053 | Sam053 | Student053 | student053@moodle.com |
      | student054 | Sam054 | Student054 | student054@moodle.com |
      | student055 | Sam055 | Student055 | student055@moodle.com |
      | student056 | Sam056 | Student056 | student056@moodle.com |
      | student057 | Sam057 | Student057 | student057@moodle.com |
      | student058 | Sam058 | Student058 | student058@moodle.com |
      | student059 | Sam059 | Student059 | student059@moodle.com |
      | student060 | Sam060 | Student060 | student060@moodle.com |
      | student061 | Sam061 | Student061 | student061@moodle.com |
      | student062 | Sam062 | Student062 | student062@moodle.com |
      | student063 | Sam063 | Student063 | student063@moodle.com |
      | student064 | Sam064 | Student064 | student064@moodle.com |
      | student065 | Sam065 | Student065 | student065@moodle.com |
      | student066 | Sam066 | Student066 | student066@moodle.com |
      | student067 | Sam067 | Student067 | student067@moodle.com |
      | student068 | Sam068 | Student068 | student068@moodle.com |
      | student069 | Sam069 | Student069 | student069@moodle.com |
      | student070 | Sam070 | Student070 | student070@moodle.com |
      | student071 | Sam071 | Student071 | student071@moodle.com |
      | student072 | Sam072 | Student072 | student072@moodle.com |
      | student073 | Sam073 | Student073 | student073@moodle.com |
      | student074 | Sam074 | Student074 | student074@moodle.com |
      | student075 | Sam075 | Student075 | student075@moodle.com |
      | student076 | Sam076 | Student076 | student076@moodle.com |
      | student077 | Sam077 | Student077 | student077@moodle.com |
      | student078 | Sam078 | Student078 | student078@moodle.com |
      | student079 | Sam079 | Student079 | student079@moodle.com |
      | student080 | Sam080 | Student080 | student080@moodle.com |
      | student081 | Sam081 | Student081 | student081@moodle.com |
      | student082 | Sam082 | Student082 | student082@moodle.com |
      | student083 | Sam083 | Student083 | student083@moodle.com |
      | student084 | Sam084 | Student084 | student084@moodle.com |
      | student085 | Sam085 | Student085 | student085@moodle.com |
      | student086 | Sam086 | Student086 | student086@moodle.com |
      | student087 | Sam087 | Student087 | student087@moodle.com |
      | student088 | Sam088 | Student088 | student088@moodle.com |
      | student089 | Sam089 | Student089 | student089@moodle.com |
      | student090 | Sam090 | Student090 | student090@moodle.com |
      | student091 | Sam091 | Student091 | student091@moodle.com |
      | student092 | Sam092 | Student092 | student092@moodle.com |
      | student093 | Sam093 | Student093 | student093@moodle.com |
      | student094 | Sam094 | Student094 | student094@moodle.com |
      | student095 | Sam095 | Student095 | student095@moodle.com |
      | student096 | Sam096 | Student096 | student096@moodle.com |
      | student097 | Sam097 | Student097 | student097@moodle.com |
      | student098 | Sam098 | Student098 | student098@moodle.com |
      | student099 | Sam099 | Student099 | student099@moodle.com |
      | student100 | Sam100 | Student100 | student100@moodle.com |
    And the following "courses" exists:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student001 | C1 | student |
      | student002 | C1 | student |
      | student003 | C1 | student |
      | student004 | C1 | student |
      | student005 | C1 | student |
      | student006 | C1 | student |
      | student007 | C1 | student |
      | student008 | C1 | student |
      | student009 | C1 | student |
      | student010 | C1 | student |
      | student011 | C1 | student |
      | student012 | C1 | student |
      | student013 | C1 | student |
      | student014 | C1 | student |
      | student015 | C1 | student |
      | student016 | C1 | student |
      | student017 | C1 | student |
      | student018 | C1 | student |
      | student019 | C1 | student |
      | student020 | C1 | student |
      | student021 | C1 | student |
      | student022 | C1 | student |
      | student023 | C1 | student |
      | student024 | C1 | student |
      | student025 | C1 | student |
      | student026 | C1 | student |
      | student027 | C1 | student |
      | student028 | C1 | student |
      | student029 | C1 | student |
      | student030 | C1 | student |
      | student031 | C1 | student |
      | student032 | C1 | student |
      | student033 | C1 | student |
      | student034 | C1 | student |
      | student035 | C1 | student |
      | student036 | C1 | student |
      | student037 | C1 | student |
      | student038 | C1 | student |
      | student039 | C1 | student |
      | student040 | C1 | student |
      | student041 | C1 | student |
      | student042 | C1 | student |
      | student043 | C1 | student |
      | student044 | C1 | student |
      | student045 | C1 | student |
      | student046 | C1 | student |
      | student047 | C1 | student |
      | student048 | C1 | student |
      | student049 | C1 | student |
      | student050 | C1 | student |
      | student051 | C1 | student |
      | student052 | C1 | student |
      | student053 | C1 | student |
      | student054 | C1 | student |
      | student055 | C1 | student |
      | student056 | C1 | student |
      | student057 | C1 | student |
      | student058 | C1 | student |
      | student059 | C1 | student |
      | student060 | C1 | student |
      | student061 | C1 | student |
      | student062 | C1 | student |
      | student063 | C1 | student |
      | student064 | C1 | student |
      | student065 | C1 | student |
      | student066 | C1 | student |
      | student067 | C1 | student |
      | student068 | C1 | student |
      | student069 | C1 | student |
      | student070 | C1 | student |
      | student071 | C1 | student |
      | student072 | C1 | student |
      | student073 | C1 | student |
      | student074 | C1 | student |
      | student075 | C1 | student |
      | student076 | C1 | student |
      | student077 | C1 | student |
      | student078 | C1 | student |
      | student079 | C1 | student |
      | student080 | C1 | student |
      | student081 | C1 | student |
      | student082 | C1 | student |
      | student083 | C1 | student |
      | student084 | C1 | student |
      | student085 | C1 | student |
      | student086 | C1 | student |
      | student087 | C1 | student |
      | student088 | C1 | student |
      | student089 | C1 | student |
      | student090 | C1 | student |
      | student091 | C1 | student |
      | student092 | C1 | student |
      | student093 | C1 | student |
      | student094 | C1 | student |
      | student095 | C1 | student |
      | student096 | C1 | student |
      | student097 | C1 | student |
      | student098 | C1 | student |
      | student099 | C1 | student |
      | student100 | C1 | student |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name | Test quiz name |
    And I wait "6" seconds
    When I follow "Test quiz name"
    And I press "Edit quiz"
    And I press "Add a question"
    And I select "True/False" radio button
    And I press "Next"
    And I fill the moodle form with:
      | Question name | First question |
      | Question text | Answer the first question |
      | General feedback | Thank you, this is the general feedback |
      | Correct answer | False
      | Feedback for the response 'True'. | So you think it is true |
      | Feedback for the response 'False'. | So you think it is false |
    And I press "Save changes"
    And I log out
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student001"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student002"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student003"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student004"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student005"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student006"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student007"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student008"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student009"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student010"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student011"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student012"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student013"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student014"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student015"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student016"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student017"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student018"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student019"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student020"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student021"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student022"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student023"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student024"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student025"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student026"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student027"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student028"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student029"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student030"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student031"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student032"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student033"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student034"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student035"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student036"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student037"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student038"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student039"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student040"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student041"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student042"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student043"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student044"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student045"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student046"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student047"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student048"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student049"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student050"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student051"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student052"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student053"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student054"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student055"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student056"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student057"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student058"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student059"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student060"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student061"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student062"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student063"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student064"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student065"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student066"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student067"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student068"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student069"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student070"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student071"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student072"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student073"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student074"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student075"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student076"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student077"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student078"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student079"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student080"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student081"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student082"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student083"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student084"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student085"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student086"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student087"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student088"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student089"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student090"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student091"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student092"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student093"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student094"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student095"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student096"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student097"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student098"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student099"
    And I give random answers to the quiz "Test quiz name" in course "Course 1" as user "student100"
