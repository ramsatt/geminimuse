import { Injectable } from '@angular/core';
import { AdMob, BannerAdOptions, BannerAdSize, BannerAdPosition, AdOptions, RewardAdPluginEvents, AdMobRewardItem } from '@capacitor-community/admob';

@Injectable({
  providedIn: 'root'
})
export class AdmobService {
  
  // Set to TRUE for development to avoid "No Fill" errors and policy violations
  // Set to FALSE for production release
  private readonly TEST_MODE = false;

  // Production Ad Unit IDs
  private readonly BANNER_ID = 'ca-app-pub-8970665297590705/9754370276';
  private readonly INTERSTITIAL_ID = 'ca-app-pub-8970665297590705/4301581666';
  private readonly REWARD_ID = 'ca-app-pub-8970665297590705/8320717729';

  // Test Ad Unit IDs (Standard Google Test IDs)
  private readonly TEST_BANNER_ID = 'ca-app-pub-3940256099942544/6300978111';
  private readonly TEST_INTERSTITIAL_ID = 'ca-app-pub-3940256099942544/1033173712';
  private readonly TEST_REWARD_ID = 'ca-app-pub-3940256099942544/5224354917';

  constructor() {
    this.initialize();
  }

  async initialize() {
    try {
       await AdMob.initialize({
         testingDevices: [],
         initializeForTesting: this.TEST_MODE,
       });
       console.log(`AdMob Initialized (${this.TEST_MODE ? 'Test' : 'Production'} Mode)`);
    } catch (e) {
      console.error('AdMob Init Error', e);
    }
  }

  async showBanner() {
    try {
      const options: BannerAdOptions = {
        adId: this.TEST_MODE ? this.TEST_BANNER_ID : this.BANNER_ID,
        adSize: BannerAdSize.ADAPTIVE_BANNER,
        position: BannerAdPosition.BOTTOM_CENTER,
        margin: 0,
        isTesting: this.TEST_MODE
      };
      await AdMob.showBanner(options);
    } catch (e) {
      console.error('Show Banner Error', e);
    }
  }

  async hideBanner() {
    try {
      await AdMob.hideBanner();
    } catch (e) {}
  }

  async removeBanner() {
    try {
      await AdMob.removeBanner();
    } catch (e) {}
  }

  async showInterstitial(): Promise<boolean> {
    try {
      const options: AdOptions = {
        adId: this.TEST_MODE ? this.TEST_INTERSTITIAL_ID : this.INTERSTITIAL_ID,
        isTesting: this.TEST_MODE
      };
      await AdMob.prepareInterstitial(options);
      await AdMob.showInterstitial();
      return true;
    } catch (e) {
      console.error('Interstitial Error', e);
      return false;
    }
  }

  async showRewardVideo(): Promise<boolean> {
    return new Promise(async (resolve) => {
      try {
        const options: AdOptions = {
          adId: this.TEST_MODE ? this.TEST_REWARD_ID : this.REWARD_ID,
          isTesting: this.TEST_MODE
        };
        
        await AdMob.prepareRewardVideoAd(options);
        
        const rewardListener = await AdMob.addListener(RewardAdPluginEvents.Rewarded, (reward: AdMobRewardItem) => {
           console.log('User rewarded', reward);
           resolve(true);
        });

        const closeListener = await AdMob.addListener(RewardAdPluginEvents.Dismissed, async () => {
           await rewardListener.remove();
           await closeListener.remove();
        });

        await AdMob.showRewardVideoAd();
        
      } catch (e) {
        console.error('Reward Ad Error', e);
        resolve(false);
      }
    });
  }
}
