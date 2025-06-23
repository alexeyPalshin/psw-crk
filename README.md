A test application that tries to crack the user password from md5 lines<br>
using [meet-in-the-middle](https://en.wikipedia.org/wiki/Meet-in-the-middle_attack) and [rainbow-table](https://en.wikipedia.org/wiki/Rainbow_table) attacks<br>
Application written in php and UI part on VUE 3 using PrimeVue UI Components

### Installation

To deploy the application on your local machine, your docker have to work in a swarm mode

1. Clone the repository.

2. Rename file ```env.example``` to ```.env.local``` in ```./src/client``` folder and in the ```./docker``` and root folders rename ```env.example``` to ```.env```

3. Build local image for Client app
```shell
docker build --build-arg port=3001 --build-arg api_url=http://psw-crack.docker.localhost  -t client-app -f ./node/Dockerfile ../src/client/
```
4. Run ```./docker/init.sh``` which build php image and deploy the stack  
5. ```docker/mysql/sql-initdb/``` folder is mapped to mysql container. put here ```not_so_smart_users.sql``` and run
```shell
 docker exec -it pswcrack_stack_db mysql -u user -psecret pswcrack < /docker-entrypoint-initdb.d/not_so_smart_users.sql
```
6. meet-in-the-middle attack uses predefined values stored in Db. To generate them run
````shell
    ./docker/sh php bin/console app:generate-first-half
````
This will generate first_half db table with all possible 3 char length variations from ```abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789```<br>
there are 240 001 rows in table.

7. rainbow-table attack uses db tables with generated chains of word hashes. For passwords that are 6 characters long and contain mixed-upper-lower-alpha-numbers<br>
we need to generate 40 000 000 rows with chain length 2400 - that will guarantee 99.9% result in resolving password<br>
Generation such tables takes time, my M1 chip was generating  2 500 000 rows for 2 hours then I stopped the process.
To not waste time You can download such tables [here](http://project-rainbowcrack.com/table.htm) for example.
Or using 
```shell
     ./docker/sh php bin/console app:generate-rainbow-6 0 9
```
simple generate table from 0 to 9 numbers for test purpose. 

That's it.

Application is available [here](http://client.psw-crack.docker.localhost/) 

Here you can see users list

And form for cracking hashes<br>
It takes  time to crack  passwords that are 6 characters long, so you may have trouble waiting and staying connected to the server.<br> 
Can be solved by further optimization, faster recognition algorithm or websockets. But then I need more time for this

| User Id |               Hash               | Password |
|:--------|:--------------------------------:|---------:|
| 2562    | 2d325a334f9755751681959a121baf26 |    11223 |
| 2519    | 501e850a01d3353409ba008b4a9a083e |    22886 |
| 2550    | fe60089838806cb8f8494de6f1470748 |    39684 |
| 2615    | 0fdb378b1ab43ee3454d31b7fa687a99 |    87411 |
| 2794    | fe54e5597c5c1a4ed9f5f0892e88a97a |     XCN2 |
| 2995    | 776081a98bebdeba0f3cf05c3be6d47c |     EII9 |
| 2832    | 4c05f70c6d6ad8c019272ac5ebff3310 |     FMS8 |
| 2627    | eda1b6f21e2822494f1eb47eabb6352e |     YQI7 |
| 2710    | b1846b4a3a5b03a74f90850178682af3 |       an |
| 2735    | 2a0eaddd9ba98b66f47ab4853280bfd8 |      bob |
| 2675    | ff2472ef6438b8a401e956a720ee66d8 |      did |
| 2977    | 3c13ffdfefc12e276b38f10cdadc76f8 |       hi |
| 2612    | fbb1a809ea0da16dfe55928b20d80a34 |       yo |
| 2712    | b939c933f8e09096c2cd92ae11d26dab |     dave |
| 2838    | b80ff0312567f0b4e4d8617c8bf2f53b |     four |
| 2959    | 954f7fcb4001afca68bb033d774dc115 |    hello |
| 2840    | 3ef8100fcb139d4f2bbb1ba884230cda |     some |
| 2666    | 08aa183aedbf25e1c10a238471a1b7fa |   monkey |
| 2517    | 6b8fbcf5755a9197b04fbdb96722dd02 |   J2ch0h |