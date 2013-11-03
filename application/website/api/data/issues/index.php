<?php

class xs_action_instance extends \xs\Action {

    function ___action () {

        $request = $this->glob->request ;

        echo "<pre>" ; print_r ( $request ) ; echo "</pre>" ;

        $email  = "Issue reporting email : \n\n" ;

        $email .= "From : ".$request->feedbackName." (".$request->feedbackUsername.") \n\n" ;
        $email .= "URL : ".$request->feedbackURL." \n\n" ;
        $email .= "Subject : ".$request->feedbackSubject." \n\n" ;
        $email .= "Comments : ".$request->feedbackComments." \n\n" ;

        echo "<pre>" ; print_r ( $email ) ; echo "</pre>" ;

        $t = mail ( $this->glob->config['website']['issues_email'], "[xSiteable] Issue reporting email", $email ) ;

        print_r ( $this->glob->config['website']['issues_email'] ) ;
        
        echo "<pre>" ; var_dump ( $t ) ; echo "</pre>" ;

        die () ;
    }

}
