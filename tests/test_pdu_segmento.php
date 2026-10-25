<?php
require __DIR__.'/../app/Services/SMPP/SmppService.php';
use App\Services\SMPP\SmppService;

$service = new SmppService('127.0.0.1', 8014, 'teste', 'nao-usada');
$bound = new ReflectionProperty(SmppService::class, 'bound');
$bound->setAccessible(true);
$bound->setValue($service, true); // teste offline: não abre socket
$socket = new ReflectionProperty(SmppService::class, 'socket');
$socket->setAccessible(true);
$socket->setValue($service, fopen('php://temp', 'w+'));
$udh = pack('C6', 5, 0, 3, 88, 3, 1);
$segmento = ['numero'=>1,'total'=>3,'esm_class'=>0x40,'data_coding'=>0,
             'short_message'=>$udh.str_repeat('A',134),'sm_length'=>140];
$seq = $service->prepareSegmentSubmit('InfoSMS','258874870386',$segmento);
$prepared = new ReflectionProperty(SmppService::class,'preparedSubmits');
$prepared->setAccessible(true);
$body = $prepared->getValue($service)[$seq];
assert(str_ends_with($body, chr(140).$segmento['short_message']));
if (!str_ends_with($body, chr(140).$segmento['short_message'])) throw new Exception('Payload incorreto');
$bad=$segmento; $bad['sm_length']=139;
try { $service->prepareSegmentSubmit('InfoSMS','258874870386',$bad); throw new Exception('Aceitou comprimento errado'); }
catch (InvalidArgumentException $e) {}
echo "OK: PDU preparado sem ligação nem envio; comprimento e UDH validados.\n";
