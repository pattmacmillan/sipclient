<?php
ob_implicit_flush();
  
  /*************************************************************/
  /*                                                           */
  /*                                                           */
  /*                        ASSIGNMENT 3                       */
  /*                       Patt MacMillan                      */
  /*                         B00534353                         */
  /*                                                           */
  /*************************************************************/


  /*************************************************************/
  /*                                                           */
  /*                                                           */
  /*                INITIAL VARIABLES & OPTIONS                */
  /*                                                           */
  /*                                                           */
  /*************************************************************/


$address = gethostbyname('bluenose.cs.dal.ca');


$max_msg_len = 1024;

//information for the socket to transmit the speed.
$port2 = 20121;
$max_msg_len2 = 2014;

//Note: argv[0] is the filename (e.g X.php)

//arguement 1 is the registration node port

$regport = $argv[1];
if($regport == "p")
  {
    $regport = 20121;
  }
if($regport > 65535 || $regport < 10000)
  {
    echo "Invalid register port. Must be between 10000 and 65535. Defaulted to 20121.\n";
    $regport = 20121;
  }
echo "Register Port: $regport\n";



//arguement 2 is the registration node addresss

$regaddr = gethostbyname($argv[2]);

if($argv[2] == "h")
  {
    $regaddr = gethostbyname('milli.cs.dal.ca');
  }
echo "Register Address: $regaddr\n";



//arguement 3 is the port that the peer should listen to the reg node from
$clientport = $argv[3];
if($clientport == "m")
  {
    $clientport = 30134;
  }
if($clientport > 65535 || $clientport < 10000)
  {
    echo "Invalid register port. Must be between 10000 and 65535. Defaulted to 30134.\n";
    $clientport = 30134;
  }



echo "Client (my) Port: $clientport\n";
echo "Client (my) Address: " . gethostname() . "\n";
$clienthost = gethostname();
$clientaddr = gethostbyname($clienthost);



//arguement 4 is the registration name the client uses for the reg node
//REQUIRED
$myidentity = $argv[4];

//the following three arguments can either be used or not. if not the client
//will wait for another peer to contact them.



//arguement 5 is the target peer
if(is_null($argv[5]))
  {
    echo "No target supplied, listening. \n";
    $targetidentity = "none";
  }
 else
   {
     $targetidentity = $argv[5];
   }



//arguement 6 is the filename
if(is_null($argv[6]))
  {
    echo "No filename supplied. \n";
    $filename = "none";
  }
 else
   {
     $filename = $argv[6];
     if(strlen($filename) > 30)
       {
	 echo "That filename is too long. Defaulting to ticker.txt.\n";
	 $filename = "ticker.txt";
       }
   }



//arguement 7 is the speed(x characters per 0.1 seconds)
if(is_null($argv[7]))
  {
    echo "No character limit supplied. \n";
    $chars = "none";
  }
 else
   {
     $chars = $argv[7];
     
     if($chars > 85)
       {
	 echo "Character count is too high. Defaulted to 85.\n";
	 $chars = 85;
       }
     else if($chars < 1)
       {
	 echo "Character count is too low. Defaulted to 1.\n";
	 $chars = 1;
       }
   }



echo "Register now? (y/n) \n";
$input = fgets(STDIN);
$yes = "y\n";
$no = "n";
//let's begin registration
/*************************************************************/
/*                                                           */
/*                                                           */
/*                        REGISTRATION                       */
/*                                                           */
/*                                                           */
/*************************************************************/

if($input == $yes)
 {
   //Create a socket to interact with the registration node
   $regsocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
   if($regsocket === false)
     {
       echo "Register socket creation failed." . socket_strerror(socket_last_error()) . "\n";
     }
   else
     {
       echo "Socket for Registration Node created! \n";
       $result3 = socket_connect($regsocket, $regaddr, $regport);
       if($result3 === false)
	 {
	   echo "REGSOCKET: socket_connect() failed.\nReason: ($result3) " . socket_strerror(socket_last_error($regsocket)) . "\n"; 
	 }
       //connection is sucessful.
       else
	 {
	   echo "Socket connect sucessful. \n\n";
	   
	   echo "Registering... \n";
	   
	   //Let's show what information we're sending to the node..
	     
	   //currently have static values for expires and callid *****
	   $callid = rand(1,100000);
	   $expires = 200;
	   echo "Name: $myidentity \n";
	   echo "Call-ID: $callid \n";
	   echo "Register Address: $regaddr \n";
	   echo "Register Port: $regport \n";
	   
	   //Now, let's put all the information into a string to send off!
	   $register = 
	     "REGISTER $myidentity SIPL/1.0\r\nTo: $myidentity\r\nFrom: $myidentity\r\nCall-ID: $callid\r\nCSeq: 0\r\nExpires: $expires\r\nContact: $clientaddr:$clientport\r\n\r\n";
	   
	   
	   //Write to socket!
	   socket_write($regsocket, $register, strlen($register));
	   
	   echo "Message sent to server. Awaiting response...\n";
	   
	   //let's read the response from the server...
	   $response = socket_read($regsocket, $max_msg_len);
	   echo "$response\n";
	   $expresponse = explode("\r\n", $response);
	   
	   //dump for testing
	   //var_dump($expresponse);
	   $okmsg = "SIPL/1.0 200 ok";
	   //exit(0);
	   $sucess = 0; 
	   
	   if($expresponse[0] == $okmsg)
	     {
	       echo "Registration sucessful! Welcome, $myidentity\n";
	       $sucess = 1;
	       echo "Closing socket.\n";
	       // socket_close($result3);
	       socket_close($regsocket);
	     }
	   else
	     {
	       echo "Registration unsucessful. \n";
	       //socket_close($result3);
	       socket_close($regsocket);
	       exit(0);
	     }
	   
	   

	   /*************************************************************/
	   /*                                                           */
	   /*                                                           */
	   /*                      RECEIVING PEER                       */
	   /*                                                           */
	   /*                                                           */
	   /*************************************************************/
	   //Now we want to see if the client that just registered is a
	   //Sending client or a listening client.
	   //We can do this by checking to see if the parameters from the
	   //command line were empty or not.
	   if($targetidentity == "none" || $filename == "none" || $chars == "none")
	     {
	       
	       echo "Would you like to open yourself up for an invitation? (y/n) \n";
	       
	       $input2 = fgets(STDIN);
	       //accepted invitation
	       if($input == $yes)
		 {

		   $acceptaddr = '0.0.0.0';
		   $acceptport = $clientport;
		   $max_msg_len = 1024;
		   $queue_len = 5;

		   echo "You've opened yourself up for an invitation. \n";
		   echo "Listening on port $acceptport \n";
        
		   
		   //Accepting The contact from the registration node.
		   

		   //$socklist = socket_create_listen($clientport);
		   $socklist = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		   socket_bind($socklist, $acceptaddr, $acceptport);
		   socket_listen($socklist, $queue_len);
		   socket_getsockname($socklist, $addr, $port);
		   echo "Server Listening on $addr:$port\n";
		      
		   
		   $c = socket_accept($socklist);
		   socket_getpeername($c, $raddr, $rport);
		   echo "Received Connection from $raddr:$rport\n";
		   //read and echo invite
		   $incmsg = socket_read($c, $max_msg_len);
		   echo "$incmsg\n";
		   $incmsgex = explode("\r\n", $incmsg);
		   echo "Message received and printed.\n";
		   echo "Passed. \n";

		   echo "Are you happy with these contraints? (y/n/c)\n Press c to change characters.";
		   $constr = fgets(STDIN);
		   $change = "c\n";
		   
		   //we want to change the number of chars
		   if($constr == $change)
		     {
		     charchoice:
		       echo "You have seleted to change the characters.\n Please enter the new number of characters per 0.1 seconds (max 85).\n";
		       $chars2 = fgets(STDIN);
		       
		       $charsexp = explode("\n", $chars2);
		       
		       if($charsexp[0] > 85)
			 {
			   echo "You entered a number above 85. Please choose again.\n";
			   goto charchoice;
			 }
		       else if($charsexp[0] < 1)
			 {
			   echo "You entered a number below 1. Please choose again.\n";
			   goto charchoice;
			 }
		     portchoice:
		       echo "Please choose a port for your peer to contact you on.\n";
		       echo "Note: Your port should be between 10000 and 65535.\n";
		       $newport = fgets(STDIN);
		       echo "You have selected port: $newport .\n";
		       $newportexp = explode("\n", $newport);
		       if($newportexp[0] < 10000)
			 {
			   echo "Your port number is too small. Please choose again.\n";
			   goto portchoice;
			 }
		       else if($newportexp[0] > 65535)
			 {
			   echo "Your port number is too large. Please choose again.\n";
			   goto portchoice;
			 }
		       
		       echo "Sending information back to the registration node...\n";
		       
		       //extract filename from invite message
		       $filename2 = $incmsgex[7];
		       $filename3 = explode("\n", $filename2);
		       //var_dump($filename3);
		       $filename4 = $filename3[0];
		       $filename5 = explode("=", $filename4);
		       $filename6 = $filename5[1];
		       echo "Filename: $filename6\n";

		       //extract sender's name
		       $incmsgex2 = $incmsgex[4];
		       echo "$incmsgex[4]\n";
		       $incmsgex3 = explode(" ", $incmsgex2);
		       echo "$incmsgex3[1]\n";
		       

		       
		       
		       $contentbody2 = "file=$filename6\ncharacters=$charsexp[0]";
		       $cb2 = strlen($contentbody2);

		       
		       $inviteback = "SIPL/1.0 200 ok\r\nTo: $incmsgex3[1]\r\nFrom: $clientaddr:$clientport\r\n$incmsgex[5]\r\n$incmsgex[3]\r\nContent-Length: strlen($contentbody2)\r\nContact: $clientaddr:$newportexp[0]\r\n\r\n$contentbody2";

		       echo "SENDING:\n\n$inviteback\n";
		       socket_write($c, $inviteback, strlen($inviteback));
                       echo "Sent back to registration node!\n";
                       
               //we can now wait for the ack message.

		       $sipmsgsock = socket_accept($socklist);
		       $ackmsgwait = socket_read($sipmsgsock, $max_msg_len);
		       echo "$ackmsgwait\n";
		       echo "We're ready to transfer data. Did you want to continue? (y/n)\n Note: n will initiate the bye message and the connection will be lost.\n";
		       $continue = fgets(STDIN);
		       
		         if($continue == $yes)
			 {
			   $oksendback = "SIPL/1.0 200 ok";
			   socket_write($sipmsgsock, $oksendback, strlen($oksendback));
			   $sipacceptaddr = '0.0.0.0';

			   //no message needs to be sent.
			   echo "Continuing...\n";
			   
			   echo "Opening transmission socket...\n";
			   if (($clientaccsock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) 
			     {
			       echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
			     }
			   echo "Accepting SIP over $sipacceptaddr:$newportexp[0]\n";

			   if (socket_bind($clientaccsock, $sipacceptaddr, $newportexp[0]) === false) 
			     {
			       echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($clientaccsock)) . "\n";
			     }

			   if (socket_listen($clientaccsock, $queue_len) === false) 
			     {
			       echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
			     }
			   //if everything passed then we can move on to accepting the transmission.
			
			   echo "transmission socket opened, accepting incomming data.\n";
			   
			   $placei = 0;
			   $placej = 0;
			   
			   while($placei <1)
			     {
			       $mysockmsg = socket_accept($clientaccsock);
			       while($placej < 1024)
				 {
				   $msgin = socket_read($mysockmsg, $max_msg_len);
				   echo "$msgin";
				   $placej ++;
				 }
			       $placei ++;
			     }
			   
			   echo "Data transmission complete!\n";
			   
			   //let's read the SIP socket to accept a final bye message.
			   $finalbye = socket_read($sipmsgsock, $max_msg_len);
			   $finalbyeexp = explode("\r\n", $finalbye);
			   //var_dump($finalbyeexp);
			   $finalbyeheader = "BYE $myidentity SIPL/1.0";
			   if($finalbyeexp[0] == $finalbyeheader)
			     {
			       echo "Your peer has initiated a connection termination.\n";
			       echo "Sending back an ok message.\n";
			       $byeokmsg = "SIPL/1.0 200 ok\r\nTo: $incmsgex3[1]\r\nFrom: $incmsgex3[1]\r\n$finalbyeexp[3]\r\n$finalbyeexp[4]\r\n\r\n";
			       socket_write($sipmsgsock, $byeokmsg, $max_msg_len);
			       echo "Ok message sent, closing socket and ending program. Thank you!\n";
			       
			       socket_close($clientaccsock);
			       socket_close($sipmsgsock);
			       socket_close($c);
			       socket_close($socklist);
			       sleep(1);
			       exit(0);
			     }
				       
			 } 
			 else
			   {
			     echo "You have chosen to end the connection with the peer.\n";
			     echo "Sending bye message...\n";
			     //incmsgex 3 for cseq 5 for call id
			     //let's figure out the last cseq call
			     $byecseq = $incmsgex[3];
			     $byecseqex = explode(" ", $byecseq);
			     $callidbye = rand(1, 100000);
			     $recvbye = "BYE $incmsgex3[1] SIPL/1.0\r\nTo: $incmsgex3[1]\r\nFrom: $myidentity\r\nCall-ID: $callidbye\r\nCSeq: $byecseqex[1]\r\n";
			     socket_write($sipmsgsock, $recvbye, $max_msg_len);
			     
			     $endingreply = socket_read($sipmsgsock, $max_msg_len);
			     $endingreplyexp = explode("\r\n", $endingreply);
			     //var_dump($endingreplyexp);
			     $endokbye = "SIPL/1.0 200 ok";
			     if($endingreplyexp[0] == $endokbye)
			       {
				 echo "End accepted. Closing program. Thank you!\n";
				 socket_close($sipmsgsock);
				 socket_close($c);
				 socket_close($socklist);
				 sleep(1);
				 exit(0);
			       }
			   }
		     }

		   
		   //Accepting the connection as is.
		   else if($constr == $yes)
		     {
		       echo"You have selected yes, you are okay with the constraints\n";
		       portchoice2:
		       echo "Please choose a port for your peer to contact you on.\n";


                       $newport = fgets(STDIN);
                       echo "You have selected port: $newport\n";
                       $newportexp = explode("\n", $newport);

		       //var_dump($newportexp);
		       echo "$newportexp[0]";
		   
		       if($newportexp[0] < 10000)
                         {
                           echo "Your port number is too small. Please choose again.\n";
			   goto portchoice2;
                         }
                       else if($newportexp[0] > 65535)
                         {
                           echo "Your port number is too large. Please choose again.\n";
			   goto portchoice2;
                         }


                       echo "Sending information back to the registration node...\n";

                       //extract filename from invite message
                       $filename2 = $incmsgex[7];
                       $filename3 = explode("\n", $filename2);
                       //var_dump($filename3);
                       $filename4 = $filename3[0];
                       $filename5 = explode("=", $filename4);
                       $filename6 = $filename5[1];
                       echo "Filename: $filename6\n";

		       //extract sender's name
                       $incmsgex2 = $incmsgex[4];
                       echo "$incmsgex[4]\n";
                       $incmsgex3 = explode(" ", $incmsgex2);
                       echo "$incmsgex3[1]\n";


                       //$contentbody2 = "file=$filename6\ncharacters=$charsexp";
                       //$cb2 = strlen($contentbody2);

		       $inviteback = "SIPL/1.0 200 ok\r\nTo: $incmsgex3[1]\r\nFrom: $clientaddr:$clientport\r\n$incmsgex[5]\r\n$incmsgex[3]\r\n$incmsgex[2]\r\nContact: $clientaddr:$newportexp[0]\r\n\r\n$incmsgex[7]";
		       
		       echo "\n\nSENDING:\n\n$inviteback\n\n\n";
		       socket_write($c, $inviteback, strlen($inviteback));
		       echo "Sent back to registration node!\n";
		       
		       
		       //we can now wait for the ack message.

		       $sipmsgsock = socket_accept($socklist);
		       $ackmsgwait = socket_read($sipmsgsock, $max_msg_len);
		       echo "$ackmsgwait\n";
		       echo "We're ready to transfer data. Did you want to continue? (y/n)\n Note: n will initiate the bye message and the connection will be lost.\n";
		       $continue = fgets(STDIN);
		       
		       if($continue == $yes)
			 {
			   $oksendback = "SIPL/1.0 200 ok";
			   socket_write($sipmsgsock, $oksendback, strlen($oksendback));
			   $sipacceptaddr = '0.0.0.0';

			   //no message needs to be sent.
			   echo "Continuing...\n";
			   
			   echo "Opening transmission socket...\n";
			   if (($clientaccsock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) 
			     {
			       echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
			     }
			   echo "Accepting SIP over $sipacceptaddr:$newportexp[0]\n";

			   if (socket_bind($clientaccsock, $sipacceptaddr, $newportexp[0]) === false) 
			     {
			       echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($clientaccsock)) . "\n";
			     }

			   if (socket_listen($clientaccsock, $queue_len) === false) 
			     {
			       echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
			     }
			   //if everything passed then we can move on to accepting the transmission.
			
			   echo "transmission socket opened, accepting incomming data.\n";
			   
			   $placei = 0;
			   $placej = 0;
			   
			   while($placei <1)
			     {
			       $mysockmsg = socket_accept($clientaccsock);
			       while($placej < 1024)
				 {
				   $msgin = socket_read($mysockmsg, $max_msg_len);
				   echo "$msgin";
				   $placej ++;
				 }
			       $placei ++;
			     }
			   
			   echo "Data transmission complete!\n";
			   
			   //let's read the SIP socket to accept a final bye message.
			   $finalbye = socket_read($sipmsgsock, $max_msg_len);
			   $finalbyeexp = explode("\r\n", $finalbye);
			   //var_dump($finalbyeexp);
			   $finalbyeheader = "BYE $myidentity SIPL/1.0";
			   if($finalbyeexp[0] == $finalbyeheader)
			     {
			       echo "Your peer has initiated a connection termination.\n";
			       echo "Sending back an ok message.\n";
			       $byeokmsg = "SIPL/1.0 200 ok\r\nTo: $incmsgex3[1]\r\nFrom: $incmsgex3[1]\r\n$finalbyeexp[3]\r\n$finalbyeexp[4]\r\n\r\n";
			       socket_write($sipmsgsock, $byeokmsg, $max_msg_len);
			       echo "Ok message sent, closing socket and ending program. Thank you!\n";
			       
			       socket_close($clientaccsock);
			       socket_close($sipmsgsock);
			       socket_close($c);
			       socket_close($socklist);
			       sleep(1);
			       exit(0);
			     }
				       
			   
			 }
		       else
			 {
			   echo "You have chosen to end the connection with the peer.\n";
			   echo "Sending bye message...\n";
			   //incmsgex 3 for cseq 5 for call id
			   //let's figure out the last cseq call
			   $byecseq = $incmsgex[3];
			   $byecseqex = explode(" ", $byecseq);
			   $callidbye = rand(1, 100000);
			   $recvbye = "BYE $incmsgex3[1] SIPL/1.0\r\nTo: $incmsgex3[1]\r\nFrom: $myidentity\r\nCall-ID: $callidbye\r\nCSeq: $byecseqex[1]\r\n";
			   socket_write($sipmsgsock, $recvbye, $max_msg_len);
			   
			   $endingreply = socket_read($sipmsgsock, $max_msg_len);
			   $endingreplyexp = explode("\r\n", $endingreply);
			   $endokbye = "SIPL/1.0 200 ok";
			   if($endingreplyexp[0] == $endokbye)
			     {
			       echo "End accepted. Closing program. Thank you!\n";
			       socket_close($sipmsgsock);
			       socket_close($c);
			       socket_close($socklist);
			       sleep(1);
			       exit(0);
			     }

			 }
		     }
		     
		     
		   //receving peer doesn't want to do anything
		   else
		     {
		       $inviteback = "SIPL/1.0 301 busy\r\n";
		       socket_close($c);
		       socket_close($socklist);
		       exit(0);
		     }
		 }

	       else
		 {
		   echo "Ending program. Thank you.\n";
		 }
	     }


	   /*************************************************************/
           /*                                                           */
           /*                                                           */
           /*                        SENDING PEER                       */
           /*                                                           */
           /*                                                           */
           /*************************************************************/
	   
	   //otherwise theyre a client with the intention of sending data
	   else
	     {
	     invite:
	       $invitesent = 0;
	       while($invitesent <= 0)
		 {
		   echo "Would you like to initiate the connection with $targetidentity ? (y/n)\n";
		   $invitepromt = fgets(STDIN);
		   
		   if($invitepromt == $yes)
		     {
		       //let's invite the other client to receive data!
		       echo "Inviting Client $targetidentity ...\n";
		       //reopen (recreate) the socket to the registration node
		       $regsocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		       if($regsocket === false)
			 {
			   echo "Socket recreation failed." . socket_strerror(socket_last_error()) . "\n";
			 }
		       else
			 {
			   $result3 = socket_connect($regsocket, $regaddr, $regport);
			   if($result3 === false)
			     {
			       echo "REGSOCKET: socket_connect() failed.\nReason: ($result3) " . socket_strerror(socket_last_error($regsocket)) . "\n";
			     }
			   //connection is sucessful.
			   else
			     {
			       echo "Socket reconnected. \n\n";
			       
			       $contentbody = "file=$filename\ncharacters=$chars";
			       $cb = strlen($contentbody);
			       $callid = rand(1,100000);
			       
			       
			       $invite = "INVITE $targetidentity SIPL/1.0\r\nTo: $targetidentity\r\nFrom: $myidentity\r\nCall-ID: $callid\r\nCSeq: 10\r\nContent-Length: $cb\r\n\r\n$contentbody";
			       echo "\n $invite \n";
			       //write the request
			       echo "Sending invite request... \n";
			       socket_write($regsocket, $invite, $max_msg_len);

			       
			       //Accepting The contact from the registration node.

			       
			       //request answer
			       $inviteanswer = socket_read($regsocket, $max_msg_len);
			       echo "$inviteanswer \n";
			      							    

			       $err406 = "SIPL/1.0 406 No contact registered for target";
			       $err301 = "SIPL/1.0 301 Busy";
			       //explode the response
			       $expinviteresponse = explode("\r\n", $inviteanswer);
			       //var_dump($expinviteresponse);
   
			       //let's check to see if the invite was sucessful.
			       if($expinviteresponse[0] == $okmsg)
				 {
				   //our connection is sucessful and parameters have been accepted!
				   echo "Connection sucessful! You can now transfer data to $targetidentity !\n";
				   
				   //let's figure out the address and port. (ticker socket)
				   $contact = $expinviteresponse[4];
				   $contactexp = explode(" ", $contact);
				   //var_dump($contactexp);
				   $contact2 = $contactexp[1];
				   $contactexp2 = explode(":", $contact2);
				   $contactaddr = $contactexp2[0];
				   $contactport = $contactexp2[1];

				   //let's figure out the address and port. (SIP socket)
                                   $sip = $expinviteresponse[2];
                                   $sipexp = explode(" ", $sip);
                                   //var_dump($sipexp);
                                   $sip2 = $sipexp[1];
                                   $sipexp2 = explode(":", $sip2);
                                   $sipaddr = $sipexp2[0];
                                   $sipport = $sipexp2[1];

				   //let's pull out cseq
				   //let's figure out the address and port. (SIP socket)
                                   $cseq = $expinviteresponse[5];
                                   $cseqexp = explode(" ", $cseq);
                                   //var_dump($cseqexp);
                                   $cseq2 = $cseqexp[1];
				   $cseq3 = $cseq2 + 1;


				   //now lets check the character speed to see if
				   //it changed.

				   $charspeed = $expinviteresponse[8];
				   $charspeedex = explode("\n", $charspeed);
				   //var_dump($charspeedex);
				   $charspeed2 = $charspeedex[1];
				   $charspeedex2 = explode("=", $charspeed2);
				   $charspeed3 = $charspeedex2[1];
				   echo "Characters: $charspeed3\n";

				   $invitesent = 1;
				   
				 }
			       else if($expinviteresponse[0] == $err301)
				 {
				   echo "Peer is busy. Please try again later.";
				 }
			       else if($expinviteresponse[0] == $err406)
				 {
				   echo "Either that client isn't registered yet or they are unwilling to take your invitiation at the moment. Please make sure your peer is registered and willing to accept requests! \n";
				   echo "Try again? (y/n)\n";
				   $tryagain = fgets(STDIN);
				   if($tryagain != $yes)
				     {
				       echo "Closing Program.\n";
				       socket_close($regsocket);
				       // socket_close($result3);
				       sleep(1);
				       exit(0);
				     }
				   socket_close($regsocket);
				   //socket_close($result3);

				 }
			       else
				 {
				   echo "Unknown error, please try again.\n";
				   socket_close($regsocket);
				   //socket_close($result3);
				   sleep(1);
				 }
			       
			 }
		     }
		 }
		   
	       else
		 {
		   echo "Please initiate invite when ready. \n";
		 }
		 }

	       //We've moved past the invitesent loop, which means we have
	       //a sucessful invite request. Now we want to acknowledge and
	       //open a connection with the receiving peer. (SIP)
	       
	       echo "Would you like to inititate the SIP command socket? (y/n)\n\n";
	       $sipyes = fgets(STDIN);
	       if($sipyes == $yes)
		 {
		   echo "Contacting peer at $sipaddr on port $sipport\n";
	       
		   //Create the new socket that will interact with the receiver.
		   $sipsocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		   if ($sipsocket === false) 
		     {
		       echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
		     } 
		   else 
		     {
		       $sipresult = socket_connect($sipsocket, $sipaddr, $sipport);
		       
		       if ($sipresult === false) 
			 {
			   echo "socket_connect() failed.\nReason: ($sipresult) " . socket_strerror(socket_last_error($sipsocket)) . "\n";
			 } 
		       else 
			 {
			   //all tests passed, let's write to the socket.
			   $callidsip = rand(1, 1000000);
			   //message of acknowledgement
			   $ackmsg = "ACK $targetidentity SIPL/1.0\r\nTo: $targetidentity\r\nFrom: $myidentity\r\nCall-ID: $callidsip\r\nCSeq: $cseq2\r\n\r\n";
			   
			   echo "Sending the acknowledgement message...\n";
			   //let's send the ack msg to the receiver
			   socket_write($sipsocket, $max_msg_len);
			   echo "Message sent.\n";
			   

			   $reply = socket_read($sipsocket, $max_msg_len);

			   $replyexp = explode("\r\n", $reply);
			   $errbye = "BYE $myidentity SIPL/1.0";
			   echo "$replyexp[0]";
			   if($replyexp[0] == $errbye)
			     {
			       $cseq4 = $cseq3 + 1;
			       $byecallid = rand(1, 10000);
			       $byeok = "SIPL/1.0 200 ok\r\nTo: $targetidentity\r\nFrom: $myidentity\r\nCall-ID: $byecallid\r\nCseq: $cseq4\r\n\r\n";
			       socket_write($sipsocket, $byeok, $max_msg_len);
			       
			       echo "The peer has declined to continue further.\n";
			       socket_close($sipsocket);
			       echo "Would you like to try to connect to a new peer?\n";
			       $newpeer = fgets(STDIN);
			       if($newpeer = $yes)
				 {
				   //connect to new peer here.
				   goto invite;
				 }
			       else
				 {
				   echo "Program ending. Thank you!\n";
				   exit(0);
				 }
			     }
			   //otherwise, let's (finally) start transmitting data!
			   else
			     {
			       //Create the new socket that will transmit data.
			       $clientsocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			       if ($clientsocket === false)
				 {
				   echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
				 }
			       else
				 {
				   $clientresult = socket_connect($clientsocket, $contactaddr, $contactport);

				   if ($clientresult === false)
				     {
                           echo "socket_connect() failed.\nReason: ($clientresult) " . socket_strerror(socket_last_error($clientsocket)) . "\n";
				     }
				   else
				     {
				       //transmission socket open for business!
				       echo "Transmission socket ready to go! Data transfer initiating...\n";
				       $handle = fopen($filename, "r");
				       $fileString = file_get_contents($filename);

				       $place = 0;
				       
				       //write the data to the scoket.
				       while($place < strlen($fileString))
					 {
					   $txmsg = substr($fileString, $place, $charspeed3);
					   socket_write($clientsocket, $txmsg, strlen($txmsg));
					   //move the appropriate amount of spaces in the string.
					   $place += $charspeed3;
					   usleep(100000);
					 }

				       //we're done writing the message.
				       socket_close($clientsocket);

				       //now that we're done transmitting, we can
				       //send a BYE message to end the transmission.
				       $mybyecallid = rand(1, 100000);
				       $cseq5 = $cseq3+2;

				       //our bye message
				       $mybye = "BYE $targetidentity SIPL/1.0\r\nTo: $targetidentity\r\nFrom: $myidentity\r\nCall-ID: $mybyecallid\r\nCSeq: $cseq5\r\n";
				       

				       //write the message to the socket
				       socket_write($sipsocket, $mybye, $max_msg_len);
				       
				       //read for a response
				       $byeprompt = socket_read($sipsocket, $max_msg_len);
				       echo "Bye prompt: $byeprompt";
				       //explode response so we can deal with it.
				       $byepromptex = explode("\r\n", $byeprompt);
				       //var_dump($byepromptex);
				       
				       if($byepromptex[0] == $okmsg)
					 {
					   echo "Peer has accepted the bye request. Disconnecting.\n";
					   
					   socket_close($sipsocket);
					   echo "Program closing. Thank you.\n";
					   exit(0);
					 }
				       else
					 {
					   echo "Undocumented error. Program closing.\n";
					   
					   socket_close($sipsocket);
					   exit(0);
					 }
				       
				       
				     }
				 }

			     }
			 }
		     }
		 }
	     }
	 }
     }
 }
 else
   {
     echo "Program ending.";
     exit(0);
   }
?>
