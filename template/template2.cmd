wait
system mkdir img#
movie reset
movie record fformat png directory ./img#/ pattern img*
roll y 2 180; wait
wait 15
roll x 2 180; wait
reset pos1; wait
reset pos1+ 50; wait
reset pos2 180; wait
reset pos1+ 180; wait
reset pos1 50; wait
reset; wait
movie stop
stop noask 
