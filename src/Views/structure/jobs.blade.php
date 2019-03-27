
    namespace App\Jobs;

    use Illuminate\Bus\Queueable;
    use Illuminate\Queue\SerializesModels;
    use Illuminate\Queue\InteractsWithQueue;
    use Illuminate\Contracts\Queue\ShouldQueue;
    use Illuminate\Foundation\Bus\Dispatchable;

    use Bus;
    use Log;
    use Exception;
    use \GuzzleHttp\Client as ClientHttp;
    use \GuzzleHttp\Exception\RequestException;

    class SynchronizeModelJob implements ShouldQueue
    {
        use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public $data;
        public $tries = 3;

        /**
         * Create a new job instance.
         *
         * @return  void
         */
        public function __construct( array $data ){
            $this->data = $data;
        }

        /**
         * Execute the job.
         *
         * @return  void
         */
        public function handle(){

            foreach ($this->data as $info) {
                try {
                    $url_api = env($info['HOST']).$info['RESOURCE'];
                    Log::info( "CURL", ['urlToSynchorinize' => $url_api, 'data' => $info] );
                    $http_client = new ClientHttp([ 'base_uri' => $url_api ]);
                    $res = $http_client->request($info['METHOD'], $url_api, [
                        /*'headers' => [
                            'Authorization' => 'Bearer '.$token
                        ],*/
                        'json' => $info['PARAMETERS']
                    ]);

                    $response = json_decode($res->getBody()->getContents(), TRUE);
                    #if( !isset($response['data']) ) //condicion si falla el api
                    #    $this->ponerEnCola( [$info] );

                } catch (Exception $e) {
                    //Debería notificar y fallar
                    #$this->ponerEnCola([$info]);
                    Log::error("ERROR SynchronizeModelJob", [$e->getMessage()]);
                    
                } catch (RequestException $re){
                    //Debería notificar y fallar
                    #$this->ponerEnCola([$info]);
                    Log::error("ERROR SynchronizeModelJob", [$e->getMessage()]);
                }

            }

        }

        public function ponerEnCola( $data ){
            
            Bus::dispatch( new SynchronizeModelJob($data) );

        }

        public function failed(Exception $e){
            #Notificar en algún lado
            Log::error("ERROR SynchronizeModelJob", [$e->getMessage()]);
        }
    }
