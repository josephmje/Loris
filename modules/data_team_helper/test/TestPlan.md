# Data Team Helper Test Plan

## [Automated testing](https://github.com/aces/Loris/blob/17.1-dev/modules/data_team_helper/test/data_team_helperTest.php) covers:
* Permissions (both present and absent)
* That body text exists

## Must be manually tested:
1. Access data_team_helper module
2. Choose a visit label and instrument and click "Show Data"
3. Ensure that Conflict/Incomplete Candidate/Link to BVL Feedback links point to the correct place.
4. Ensure that feedback status is correct.
5. Click on a field label and ensure that it downloads CSV of the data.
6. Repeat steps 2-5 with various instruments and visit labels.
7. Perform a query that returns a row with a link to BVL feedback. Click on the click to edit the feedback QC
   status and change its value. Save the data and close the popup. Make sure that the DQH result table has been 
   updated for that row (i.e. reflect the new Feedback status). Click on the feedback link again and check that 
   the QC status has also been updated in the popup window. 
