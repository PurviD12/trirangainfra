import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { InquiryViewFollowUpHistoryComponent } from './inquiry-view-follow-up-history.component';

describe('InquiryViewFollowUpHistoryComponent', () => {
    let component: InquiryViewFollowUpHistoryComponent;
    let fixture: ComponentFixture<InquiryViewFollowUpHistoryComponent>;

    beforeEach(async(() => {
        TestBed.configureTestingModule({
            declarations: [InquiryViewFollowUpHistoryComponent]
        })
            .compileComponents();
    }));

    beforeEach(() => {
        fixture = TestBed.createComponent(InquiryViewFollowUpHistoryComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
