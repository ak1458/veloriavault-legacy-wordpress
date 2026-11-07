import test from 'node:test';
import assert from 'node:assert/strict';

let replies = [];
let requests = [];
let reloads = 0;
let confirmations = [];
let alerts = [];
globalThis.window = {
  veloriaConfig: { ajaxUrl:'https://store.test/ajax', restUrl:'https://store.test/wp-json/veloria/v1/', urls:{account:'/account',login:'/login'} },
  location:{origin:'https://store.test',hash:'',reload(){++reloads;},assign(){}},
  confirm(message){confirmations.push(message);return true;}, alert(message){alerts.push(message);}
};
globalThis.fetch = async (url, options={}) => {
  if (String(url).includes('/ajax?')) return {ok:true,json:async()=>({rest:'fixture',store:'fixture'})};
  requests.push({url:String(url),options});
  assert.ok(replies.length, 'Unexpected request '+url);
  const response = replies.shift();
  if (response instanceof Error) throw response;
  return {ok:response.ok??true,status:response.status??200,json:async()=>response.data};
};
const {trackOrder,account,nativeOrderTools} = await import('../src/js/components/account.js');
function reset(...responses){replies=responses;requests=[];reloads=0;confirmations=[];alerts=[];}
const original = {id:42,number:'42',status:'processing',canCancel:true,cancelMode:'automatic',canReturn:true,isCod:true,codDue:'1274.00',amountRefunded:'0.00'};
const reviewMessage = 'Cancellation review requested. Your order is on hold while our team checks whether it can be stopped. No refund has been issued yet.';
const reviewResponse = {success:true,pendingReview:true,refunded:false,message:reviewMessage};
const reviewed = {...original,status:'on-hold',canCancel:false,cancelMode:'review',cancelReviewState:'requested',refundProgress:reviewMessage};

test('successful cancellation replaces eligibility and COD due from the server', async()=>{
  reset({data:{success:true,refunded:false}},{data:{order:{...original,status:'cancelled',canCancel:false,canReturn:false,codDue:null}}});
  const view=trackOrder();view.order={...original};await view.cancel();
  assert.equal(view.order.canCancel,false);assert.equal(view.order.codDue,null);assert.equal(view.order.status,'cancelled');
  assert.equal(view.actionLoading,false);assert.equal(replies.length,0);
});
test('accepted refund refreshes progress even when cancellation returns 409', async()=>{
  reset({ok:false,status:409,data:{error:'Pending'}},{data:{order:{...original,status:'on-hold',refundProgress:'Refund pending',amountRefunded:'0.00'}}});
  const view=trackOrder();view.order={...original};await view.cancel();
  assert.equal(view.order.status,'on-hold');assert.equal(view.actionMsg.text,'Refund pending');assert.equal(view.actionMsg.type,'info');
});
test('successful return replaces eligibility rather than offering another return',async()=>{
  reset({data:{success:true}},{data:{order:{...original,status:'on-hold',canReturn:false,returnRequested:true,returnState:'requested'}}});
  const view=trackOrder();view.order={...original};view.returnReason='Wrong item';view.showReturn=true;await view.submitReturn();
  assert.equal(view.order.canReturn,false);assert.equal(view.order.returnState,'requested');assert.equal(view.showReturn,false);
});
test('failed refresh stops repeated order actions without inventing fresh state',async()=>{
  reset({data:{success:true}},{ok:false,status:503,data:{error:'Unavailable'}});
  const view=trackOrder();view.order={...original};await view.cancel();const count=requests.length;await view.cancel();
  assert.equal(view.orderStateStale,true);assert.equal(requests.length,count);assert.equal(view.actionLoading,false);
});
test('account cancellation reloads the full order list and tracking selection',async()=>{
  const updated={...original,status:'cancelled',canCancel:false,codDue:null};
  reset({data:{success:true}},{data:{orders:[updated]}});
  const view=account();view.orders=[{...original}];view.trackingOrder=view.orders[0];await view.cancel(view.orders[0]);
  assert.equal(view.orders[0].canCancel,false);assert.equal(view.trackingOrder.codDue,null);assert.equal(view.ordersStateStale,false);
});
test('returns tab contains actual requested, approved and refunded orders',()=>{
  const view=account();view.orders=[original,{id:2,returnRequested:true,returnState:'requested'},{id:3,returnRequested:true,returnState:'approved'},{id:4,amountRefunded:'50.00'}];
  assert.deepEqual(view.returnOrders.map(o=>o.id),[2,3,4]);assert.equal(view.returnStatus(view.returnOrders[0]),'Return awaiting inspection');
  assert.equal(view.returnStatus(view.returnOrders[1]),'Return approved after inspection');assert.equal(view.returnStatus(view.returnOrders[2]),'Refund recorded');
});
test('native order screen reloads after success and a changed held state',async()=>{
  reset({data:{success:true}});await nativeOrderTools(42,null,'processing').cancel();assert.equal(reloads,1);
  reset({ok:false,status:409,data:{error:'Pending'}},{data:{order:{...original,status:'on-hold',refundProgress:'Pending'}}});
  await nativeOrderTools(42,null,'processing').cancel();assert.equal(reloads,1);
});
test('native recovery sends the owned order email required by the track endpoint',async()=>{
  reset({ok:false,status:409,data:{error:'Pending'}},{data:{order:{...original,status:'on-hold',refundProgress:'Pending'}}});
  await nativeOrderTools(42,'owner@example.test','processing','automatic').cancel();
  const refresh=new URL(requests[1].url);assert.equal(refresh.searchParams.get('email'),'owner@example.test');
  assert.equal(refresh.searchParams.get('orderNumber'),'42');assert.equal(reloads,1);
});
test('tracker review success remains a held request without claiming cancellation or refund',async()=>{
  reset({data:reviewResponse},{data:{order:reviewed}});
  const view=trackOrder();view.order={...original,cancelMode:'review'};await view.cancel();
  assert.equal(view.order.status,'on-hold');assert.equal(view.order.canCancel,false);assert.equal(view.order.cancelReviewState,'requested');
  assert.equal(view.actionMsg.type,'info');assert.equal(view.actionMsg.text,reviewMessage);
  assert.doesNotMatch(view.actionMsg.text,/order cancelled|refund has been initiated/i);
  const count=requests.length;await view.cancel();assert.equal(requests.length,count);
});
test('account review success refreshes eligibility and explains review instead of cancellation',async()=>{
  reset({data:reviewResponse},{data:{orders:[reviewed]}});
  const view=account();view.orders=[{...original,cancelMode:'review'}];await view.cancel(view.orders[0]);
  assert.equal(view.orders[0].canCancel,false);assert.equal(view.orders[0].cancelReviewState,'requested');assert.equal(alerts[0],reviewMessage);
  assert.equal(view.returnStatus(view.returnOrders[0]),'Cancellation awaiting review');
  assert.doesNotMatch(alerts[0],/order cancelled|refund has been initiated/i);
});
test('native review success reloads for authoritative state and retains honest pending message',async()=>{
  reset({data:reviewResponse});const view=nativeOrderTools(42,null,'processing','review');await view.cancel();
  assert.equal(reloads,1);assert.equal(view.canCancel,false);assert.equal(view.message,reviewMessage);
  assert.doesNotMatch(view.message,/order cancelled|refund has been initiated/i);
});
test('all cancellation surfaces confirm staff review without assuming the order is older than 48 hours',async()=>{
  reset({data:reviewResponse},{data:{order:reviewed}});
  const tracker=trackOrder();tracker.order={...original,cancelMode:'review'};await tracker.cancel();
  const trackerConfirmation=confirmations[0];
  reset({data:reviewResponse},{data:{orders:[reviewed]}});
  const dashboard=account();await dashboard.cancel({...original,cancelMode:'review'});const accountConfirmation=confirmations[0];
  reset({data:reviewResponse});await nativeOrderTools(42,null,'processing','review').cancel();
  for(const message of [trackerConfirmation,accountConfirmation,confirmations[0]]){
    assert.match(message,/Request cancellation review/);assert.match(message,/must approve/);assert.match(message,/before any refund/);
    assert.doesNotMatch(message,/More than 48|Order cancelled|processed automatically/i);
  }
});
test('review and automatic labels match the action offered by the server',()=>{
  for(const view of [trackOrder(),account()]){
    assert.equal(view.cancellationLabel({cancelMode:'review'}),'Request cancellation review');
    assert.equal(view.cancellationLabel({cancelMode:'automatic'}),'Cancel order');
  }
  assert.equal(nativeOrderTools(42,null,'processing','review').cancelLabel,'Request cancellation review');
});
test('automatic confirmation explains courier checks, original source, bank timing and unpaid COD',async()=>{
  reset({data:{success:true,refunded:false}},{data:{order:{...original,status:'cancelled',canCancel:false}}});
  const view=trackOrder();view.order={...original};await view.cancel();
  assert.match(confirmations[0],/original payment method/);assert.match(confirmations[0],/Courier checks may need staff help/);
  assert.match(confirmations[0],/Bank timing varies/);assert.match(confirmations[0],/unpaid COD order has no refund/);
  assert.doesNotMatch(confirmations[0],/processed automatically|within 48 hours/i);
});
test('review state is visible even without a return or gateway refund record',()=>{
  const view=account();view.orders=[original,{id:7,cancelReviewState:'declined'},{id:8,cancelReviewState:'approved'}];
  assert.deepEqual(view.returnOrders.map(order=>order.id),[7,8]);
  assert.equal(view.returnStatus(view.returnOrders[0]),'Cancellation request declined');
  assert.equal(view.returnStatus(view.returnOrders[1]),'Cancellation approved; checking refund progress');
  assert.equal(view.returnStatus({...original,status:'cancelled',cancelReviewState:'approved'}),'Order cancelled');
});
