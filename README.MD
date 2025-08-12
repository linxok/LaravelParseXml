# testUAITLAB
open terminal, 
    sh restart.sh    or     sh stop.sh
        run/stop docker-compose.yaml

    docker exec -it web bash
    cd app
    php artisan migrate
    
    php artisan app:xml-parse test.xml