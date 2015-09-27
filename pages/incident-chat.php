<div id="alert-map" role="alert" class="alert alert-warning alert-dismissible fade in">
    <button id="show-incident-map-btn" class="btn btn-default" type="button">Show Map</button>
    <button id="hide-incident-map-btn" class="btn btn-default" type="button">Hide Map</button>
</div>
<div id="map-container" class="container-fluid">
    <div id="map"></div>
</div>
<iframe id="chat-frame" src="https://tlk.io/<?php print esc_attr($this->getChatRoomName());?>" width="100%" height="100%" frameborder="0"></iframe>

<div id="safety-information-modal" class="modal fade" role="dialog" aria-labelledby="safety-information-modal-label">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="safety-information-modal-label">Safety information</h4>
            </div>
            <div class="modal-body">
                <h1>Emergency Numbers</h1>
                <ul>
                    <li>Seek a shelter for victims of domestic violence, it is always safe to call the shelter</li>
                    <li>Call the National Domestic Violence Hotline and they can provide you the location of the nearest shelter:  1-800-799-7233</li>
                    <li>Love is Respect - To speak to a supportive peer advocate call: <a href="tel:18663319474">1.866.331.9474</a>, text: <a href="sms:22522?body=loveis">loveis to 22522</a>, or chat 24/7 at <a href="http://loveisrespect.org/">loveisrespect.org</a></li>
                    <li>New Mexico Crisis and Access Line can provide directions to Shelters, refer to Mental Health, Detox, Vocational assistance, Legal Assistance: <a href="tel:18556627474">1-855-662-7474</a></li>
                    <li>If you have a companion animal you need to keep safe but can’t take with you this number is to the New Mexico Companion Animal Rescue Effort (CARE):
                        <ul>
                            <li><a href="tel:18443252273">1-844-325-2273</a></li>
                            <li>TTY or TTD: <a href="tel:18006598831">1-800-659-8831</a></li>
                        </ul>
                    </li>
                </ul>
                <h1>Staying Safe</h1>
                <ul>
                    <li>If your abuser is becoming violent, or you believe they could be, leave as quickly and safely as possible and call 911</li>
                    <li>When you decide to leave, determine a safe route for escape</li>
                    <li>If an argument seems unavoidable, stay away from any rooms such as the kitchen, bathroom, and garage where you might be trapped or there might be weapons readily available</li>
                    <li>If you can, pack a bag for you and your children with a change of clothes and all important documents such as birth certificates, social security cards, shot records, check books, ATM cards, drives license, and any medication and money you have, and put it in a safe place where you can access it quickly, like in the trunk of your car or at a friend’s house</li>
                    <li>If you choose to use alcohol or drugs it is important to be aware of the potential dangers</li>
                    <li>Teach your children how to call 911 and let them know it is okay to call if there is any violence happening</li>
                    <li>Be aware of your surroundings and others at all times</li>
                    <li>Do not go to places where your abuser or their friends frequent</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div><!-- .modal-content -->
    </div><!-- .modal-dialog -->
</div><!-- .modal.fade -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
